<?php

namespace Tests\Feature\Inventory;

use App\Actions\Inventory\RecordOpeningStock;
use App\Actions\Procurement\AllocateSupplierAdvance;
use App\Actions\Procurement\CreatePurchaseBill;
use App\Actions\Procurement\CreateSupplier;
use App\Actions\Procurement\PostPurchaseBill;
use App\Actions\Procurement\RecordSupplierPayment;
use App\Actions\Procurement\ReverseSupplierPayment;
use App\Enums\InventoryMode;
use App\Enums\MembershipStatus;
use App\Enums\SupplierLedgerEntryType;
use App\Enums\SupplierPaymentMode;
use App\Enums\UserStatus;
use App\Models\Business;
use App\Models\BusinessMember;
use App\Models\Item;
use App\Models\Role;
use App\Models\StockBalance;
use App\Models\Supplier;
use App\Models\SupplierLedgerEntry;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserRole;
use App\Models\Warehouse;
use App\Services\Inventory\ReconciliationService;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ReconciliationTest extends TestCase
{
    use DatabaseTransactions;

    protected Business $business;

    protected User $owner;

    protected Warehouse $warehouse;

    protected Item $item;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);

        $this->business = Business::factory()->create();
        $this->owner = User::factory()->create(['status' => UserStatus::Active]);

        BusinessMember::create([
            'business_id' => $this->business->id,
            'user_id' => $this->owner->id,
            'is_owner' => true,
            'status' => MembershipStatus::Active,
            'joined_at' => now(),
        ]);

        $ownerRole = Role::where('slug', 'owner')->whereNull('business_id')->firstOrFail();
        UserRole::create([
            'user_id' => $this->owner->id,
            'role_id' => $ownerRole->id,
            'business_id' => $this->business->id,
        ]);

        $this->warehouse = Warehouse::create([
            'business_id' => $this->business->id,
            'name' => 'Central Depot',
            'code' => 'CENTRAL',
            'is_default' => true,
            'status' => 'active',
        ]);

        $unit = Unit::factory()->create(['business_id' => $this->business->id]);
        $this->item = Item::factory()->create([
            'business_id' => $this->business->id,
            'unit_id' => $unit->id,
            'inventory_mode' => InventoryMode::Normal,
        ]);
    }

    public function test_reconciliation_passes_on_consistent_stock_ledger(): void
    {
        $openingStockAction = app(RecordOpeningStock::class);
        $openingStockAction->execute($this->owner, $this->business, [
            'item_id' => $this->item->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 25,
            'unit_cost' => 50,
        ]);

        $service = app(ReconciliationService::class);
        $report = $service->reconcile($this->business);

        $this->assertTrue($report['reconciled']);
        $this->assertEmpty($report['stock_discrepancies']);
        $this->assertEmpty($report['unit_discrepancies']);
    }

    public function test_reconciliation_detects_deliberate_balance_discrepancy(): void
    {
        $openingStockAction = app(RecordOpeningStock::class);
        $openingStockAction->execute($this->owner, $this->business, [
            'item_id' => $this->item->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 25,
            'unit_cost' => 50,
        ]);

        // Deliberately corrupt balance record (simulate unauthorized mutation or data corruption)
        StockBalance::where('business_id', $this->business->id)
            ->where('item_id', $this->item->id)
            ->update(['quantity' => 99]);

        $service = app(ReconciliationService::class);
        $report = $service->reconcile($this->business);

        $this->assertFalse($report['reconciled']);
        $this->assertCount(1, $report['stock_discrepancies']);
        $this->assertEquals(99.0, (float) $report['stock_discrepancies'][0]['balance_quantity']);
        $this->assertEquals(25.0, (float) $report['stock_discrepancies'][0]['movement_quantity']);
    }

    public function test_reconciliation_artisan_command_detects_clean_and_corrupt_states(): void
    {
        $this->artisan('inventory:reconcile', ['business_id' => $this->business->id])
            ->assertExitCode(0);

        // Inject discrepancy
        StockBalance::create([
            'business_id' => $this->business->id,
            'warehouse_id' => $this->warehouse->id,
            'item_id' => $this->item->id,
            'quantity' => 15, // No corresponding movement
        ]);

        $this->artisan('inventory:reconcile', ['business_id' => $this->business->id])
            ->assertExitCode(1);
    }

    public function test_supplier_reconciliation_passes_across_complete_procurement_lifecycle(): void
    {
        // 1. Onboard supplier with opening balance of 5,000.00
        $supplier = app(CreateSupplier::class)->execute($this->owner, $this->business, [
            'name' => 'Authoritative Parts Ltd',
            'opening_balance' => 5000.00,
        ]);

        // 2. Post Bill 1 for 12,000.00
        $bill1 = app(CreatePurchaseBill::class)->execute($this->owner, $this->business, [
            'supplier_id' => $supplier->id,
            'supplier_invoice_number' => 'BILL-CYCLE-01',
            'lines' => [
                ['item_id' => $this->item->id, 'quantity' => 120, 'unit_cost' => 100.00, 'tax_rate' => 0],
            ],
        ]);
        $bill1 = app(PostPurchaseBill::class)->execute($this->owner, $this->business, $bill1);

        // 3. Post Payment 1 for 15,000.00 (10,000 allocated to Bill 1, 5,000 unapplied advance)
        $payment1 = app(RecordSupplierPayment::class)->execute($this->owner, $this->business, [
            'supplier_id' => $supplier->id,
            'amount' => 15000.00,
            'payment_mode' => SupplierPaymentMode::BankTransfer->value,
            'allocations' => [
                ['purchase_bill_id' => $bill1->id, 'amount' => 10000.00],
            ],
        ]);

        // 4. Post Bill 2 for 8,000.00
        $bill2 = app(CreatePurchaseBill::class)->execute($this->owner, $this->business, [
            'supplier_id' => $supplier->id,
            'supplier_invoice_number' => 'BILL-CYCLE-02',
            'lines' => [
                ['item_id' => $this->item->id, 'quantity' => 80, 'unit_cost' => 100.00, 'tax_rate' => 0],
            ],
        ]);
        $bill2 = app(PostPurchaseBill::class)->execute($this->owner, $this->business, $bill2);

        // 5. Allocate 3,000.00 of Payment 1 advance to Bill 2
        app(AllocateSupplierAdvance::class)->execute($this->owner, $this->business, $payment1, $bill2, 3000.00);

        // 6. Post Payment 2 for 5,000.00 (fully settling remaining Bill 2)
        $payment2 = app(RecordSupplierPayment::class)->execute($this->owner, $this->business, [
            'supplier_id' => $supplier->id,
            'amount' => 5000.00,
            'payment_mode' => SupplierPaymentMode::BankTransfer->value,
            'allocations' => [
                ['purchase_bill_id' => $bill2->id, 'amount' => 5000.00],
            ],
        ]);

        // 7. Reverse Payment 2 (bounced / cancelled)
        app(ReverseSupplierPayment::class)->execute($this->owner, $this->business, $payment2, 'Bounced NEFT transfer');

        // Net Accounting Balance Check:
        // Opening (5,000) + Bill 1 (12,000) + Bill 2 (8,000) - Active Payment 1 (15,000) - Active Payment 2 (0, reversed)
        // = 10,000.00
        $service = app(ReconciliationService::class);
        $report = $service->reconcile($this->business);

        $this->assertTrue($report['reconciled']);
        $this->assertEquals('clean', $report['status']);
        $this->assertEmpty($report['supplier_payable_discrepancies']);
    }

    public function test_supplier_reconciliation_detects_deliberate_subledger_amount_corruption(): void
    {
        $supplier = app(CreateSupplier::class)->execute($this->owner, $this->business, [
            'name' => 'Integrity Check Supplier',
            'opening_balance' => 0,
        ]);

        $bill = app(CreatePurchaseBill::class)->execute($this->owner, $this->business, [
            'supplier_id' => $supplier->id,
            'supplier_invoice_number' => 'BILL-CORRUPT-01',
            'lines' => [
                ['item_id' => $this->item->id, 'quantity' => 10, 'unit_cost' => 100.00, 'tax_rate' => 0],
            ],
        ]);
        app(PostPurchaseBill::class)->execute($this->owner, $this->business, $bill);

        // Deliberately corrupt the subledger entry amount (from 1000.00 to 700.00)
        SupplierLedgerEntry::where('supplier_id', $supplier->id)
            ->where('source_id', $bill->id)
            ->update(['amount' => 700.00]);

        $service = app(ReconciliationService::class);
        $report = $service->reconcile($this->business);

        $this->assertFalse($report['reconciled']);
        $this->assertEquals('discrepancies_found', $report['status']);
        $this->assertCount(1, $report['supplier_payable_discrepancies']);

        $disc = $report['supplier_payable_discrepancies'][0];
        $this->assertEquals($supplier->id, $disc['supplier_id']);
        $this->assertEquals(700.00, (float) $disc['ledger_balance']);
        $this->assertEquals(1000.00, (float) $disc['expected_balance']);
        $this->assertEquals(-300.00, (float) $disc['difference']);
        $this->assertTrue($disc['ledger_mismatch']);
    }

    public function test_supplier_reconciliation_detects_rogue_subledger_entry(): void
    {
        $supplier = app(CreateSupplier::class)->execute($this->owner, $this->business, [
            'name' => 'Rogue Check Supplier',
            'opening_balance' => 0,
        ]);

        // Insert unbacked rogue subledger entry
        SupplierLedgerEntry::create([
            'business_id' => $this->business->id,
            'supplier_id' => $supplier->id,
            'entry_type' => SupplierLedgerEntryType::Adjustment,
            'amount' => 2500.00,
            'running_balance' => 2500.00,
            'source_type' => 'manual_tampering',
            'source_id' => 'fake-id',
            'source_reference' => 'UNAUTHORIZED',
            'posted_at' => now(),
            'actor_id' => $this->owner->id,
            'notes' => 'Tampered credit',
        ]);

        $service = app(ReconciliationService::class);
        $report = $service->reconcile($this->business);

        $this->assertFalse($report['reconciled']);
        $this->assertCount(1, $report['supplier_payable_discrepancies']);
        $this->assertEquals(2500.00, (float) $report['supplier_payable_discrepancies'][0]['ledger_balance']);
        $this->assertEquals(0.00, (float) $report['supplier_payable_discrepancies'][0]['expected_balance']);
    }

    public function test_supplier_reconciliation_detects_tampered_bill_allocation(): void
    {
        $supplier = app(CreateSupplier::class)->execute($this->owner, $this->business, [
            'name' => 'Allocation Tamper Supplier',
            'opening_balance' => 0,
        ]);

        $bill = app(CreatePurchaseBill::class)->execute($this->owner, $this->business, [
            'supplier_id' => $supplier->id,
            'supplier_invoice_number' => 'BILL-TAMPER-01',
            'lines' => [
                ['item_id' => $this->item->id, 'quantity' => 10, 'unit_cost' => 100.00, 'tax_rate' => 0],
            ],
        ]);
        $bill = app(PostPurchaseBill::class)->execute($this->owner, $this->business, $bill);

        // Deliberately tamper with bill paid_amount without a corresponding payment or allocation
        $bill->update(['paid_amount' => 400.00]);

        $service = app(ReconciliationService::class);
        $report = $service->reconcile($this->business);

        $this->assertFalse($report['reconciled']);
        $this->assertCount(1, $report['supplier_payable_discrepancies']);
        $disc = $report['supplier_payable_discrepancies'][0];
        $this->assertTrue($disc['allocation_mismatch']);
        $this->assertEquals(1000.00, (float) $disc['expected_balance']);
        $this->assertEquals(600.00, (float) $disc['open_items_balance']);
    }
}
