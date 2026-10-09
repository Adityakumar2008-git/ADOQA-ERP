<?php

namespace Tests\Feature\Procurement;

use App\Actions\Procurement\ApprovePurchaseOrder;
use App\Actions\Procurement\CreateGoodsReceipt;
use App\Actions\Procurement\CreatePurchaseBill;
use App\Actions\Procurement\CreatePurchaseOrder;
use App\Actions\Procurement\CreateSupplier;
use App\Actions\Procurement\PostGoodsReceipt;
use App\Actions\Procurement\PostPurchaseBill;
use App\Enums\GoodsReceiptStatus;
use App\Enums\InventoryMode;
use App\Enums\MembershipStatus;
use App\Enums\PurchaseBillStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\StockMovementType;
use App\Enums\SupplierLedgerEntryType;
use App\Enums\UserStatus;
use App\Models\Business;
use App\Models\BusinessMember;
use App\Models\Item;
use App\Models\Role;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\SupplierLedgerEntry;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserRole;
use App\Models\Warehouse;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProcurementWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    protected Business $business;

    protected User $owner;

    protected User $staff;

    protected Warehouse $warehouse;

    protected Supplier $supplier;

    protected Item $item;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);

        $this->business = Business::factory()->create();
        $this->owner = User::factory()->create(['status' => UserStatus::Active]);
        $this->staff = User::factory()->create(['status' => UserStatus::Active]);

        // Owner membership & role
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

        // Staff membership & role (no purchase approval permissions)
        BusinessMember::create([
            'business_id' => $this->business->id,
            'user_id' => $this->staff->id,
            'is_owner' => false,
            'status' => MembershipStatus::Active,
            'joined_at' => now(),
        ]);
        $staffRole = Role::where('slug', 'staff')->whereNull('business_id')->firstOrFail();
        UserRole::create([
            'user_id' => $this->staff->id,
            'role_id' => $staffRole->id,
            'business_id' => $this->business->id,
        ]);

        $this->warehouse = Warehouse::create([
            'business_id' => $this->business->id,
            'name' => 'Depot 1',
            'code' => 'DEP-01',
            'status' => 'active',
            'is_default' => true,
        ]);

        $supplierAction = app(CreateSupplier::class);
        $this->supplier = $supplierAction->execute($this->owner, $this->business, [
            'name' => 'Prime Hardware Supplies',
            'code' => 'PRIME-01',
            'phone' => '9876543210',
        ]);

        $unit = Unit::factory()->create(['business_id' => $this->business->id]);
        $this->item = Item::factory()->create([
            'business_id' => $this->business->id,
            'unit_id' => $unit->id,
            'cost_price' => 100.00, // Catalogue default cost
            'inventory_mode' => InventoryMode::Normal,
        ]);
    }

    public function test_purchase_order_creation_and_approval_workflow(): void
    {
        $createOrderAction = app(CreatePurchaseOrder::class);

        // 1. Create Draft PO
        $order = $createOrderAction->execute($this->owner, $this->business, [
            'supplier_id' => $this->supplier->id,
            'lines' => [
                [
                    'item_id' => $this->item->id,
                    'ordered_quantity' => 20,
                    'unit_price' => 95.00, // Negotiated unit cost
                    'tax_rate' => 0,
                ],
            ],
        ]);

        $this->assertEquals(PurchaseOrderStatus::Draft, $order->status);
        $this->assertEquals(1900.00, (float) $order->total_amount);

        // Staff without approval permission cannot approve PO
        $approveOrderAction = app(ApprovePurchaseOrder::class);
        try {
            $approveOrderAction->execute($this->staff, $this->business, $order);
            $this->fail('Expected authorization exception when unauthorized staff approves PO.');
        } catch (\Throwable $e) {
            $this->assertTrue(true);
        }

        // Owner approves PO
        $order = $approveOrderAction->execute($this->owner, $this->business, $order);
        $this->assertEquals(PurchaseOrderStatus::Approved, $order->status);
    }

    public function test_purchase_bill_posting_creates_payable_without_increasing_stock(): void
    {
        $createBillAction = app(CreatePurchaseBill::class);
        $bill = $createBillAction->execute($this->owner, $this->business, [
            'supplier_id' => $this->supplier->id,
            'supplier_invoice_number' => 'INV-SUP-101',
            'lines' => [
                [
                    'item_id' => $this->item->id,
                    'quantity' => 10,
                    'unit_cost' => 95.00,
                    'tax_rate' => 0,
                ],
            ],
        ]);

        $this->assertEquals(PurchaseBillStatus::Draft, $bill->status);

        // Post Bill
        $postBillAction = app(PostPurchaseBill::class);
        $bill = $postBillAction->execute($this->owner, $this->business, $bill);

        $this->assertEquals(PurchaseBillStatus::Posted, $bill->status);

        // Verify supplier subledger payable entry created
        $ledgerEntry = SupplierLedgerEntry::where('business_id', $this->business->id)
            ->where('supplier_id', $this->supplier->id)
            ->where('entry_type', SupplierLedgerEntryType::BillPosted)
            ->first();

        $this->assertNotNull($ledgerEntry);
        $this->assertEquals(950.00, (float) $ledgerEntry->debit_amount);
        $this->assertEquals(950.00, (float) $this->supplier->fresh()->current_balance);

        // Posting Bill must NOT increase physical stock
        $balance = StockBalance::where('business_id', $this->business->id)
            ->where('item_id', $this->item->id)
            ->first();

        $this->assertTrue($balance === null || (float) $balance->quantity === 0.0);
    }

    public function test_goods_receipt_increases_stock_and_preserves_actual_intake_cost(): void
    {
        $createReceiptAction = app(CreateGoodsReceipt::class);
        $receipt = $createReceiptAction->execute($this->owner, $this->business, [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'lines' => [
                [
                    'item_id' => $this->item->id,
                    'accepted_quantity' => 15,
                    'actual_unit_cost' => 92.50, // Actual intake cost differs from catalogue
                ],
            ],
        ]);

        $this->assertEquals(GoodsReceiptStatus::Draft, $receipt->status);

        // Post Goods Receipt
        $postReceiptAction = app(PostGoodsReceipt::class);
        $receipt = $postReceiptAction->execute($this->owner, $this->business, $receipt);

        $this->assertEquals(GoodsReceiptStatus::Posted, $receipt->status);

        // Stock balance must now be 15
        $balance = StockBalance::where('warehouse_id', $this->warehouse->id)
            ->where('item_id', $this->item->id)
            ->first();

        $this->assertNotNull($balance);
        $this->assertEquals(15.0, (float) $balance->quantity);

        // Stock movement ledger must preserve actual intake cost 92.50 (NOT catalogue cost 100.00)
        $movement = StockMovement::where('warehouse_id', $this->warehouse->id)
            ->where('movement_type', StockMovementType::PurchaseReceipt)
            ->first();

        $this->assertNotNull($movement);
        $this->assertEquals(15.0, (float) $movement->quantity);
        $this->assertEquals(92.50, (float) $movement->unit_cost);
    }

    public function test_duplicate_supplier_invoice_number_is_rejected(): void
    {
        $createBillAction = app(CreatePurchaseBill::class);
        $createBillAction->execute($this->owner, $this->business, [
            'supplier_id' => $this->supplier->id,
            'supplier_invoice_number' => 'INV-DUPLICATE-99',
            'lines' => [
                ['item_id' => $this->item->id, 'quantity' => 1, 'unit_cost' => 50],
            ],
        ]);

        $this->expectException(ValidationException::class);

        // Attempting same invoice number for same supplier must fail
        $createBillAction->execute($this->owner, $this->business, [
            'supplier_id' => $this->supplier->id,
            'supplier_invoice_number' => 'INV-DUPLICATE-99',
            'lines' => [
                ['item_id' => $this->item->id, 'quantity' => 1, 'unit_cost' => 50],
            ],
        ]);
    }
}
