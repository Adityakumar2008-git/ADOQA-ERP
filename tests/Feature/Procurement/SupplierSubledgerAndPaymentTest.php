<?php

namespace Tests\Feature\Procurement;

use App\Actions\Procurement\AllocateSupplierAdvance;
use App\Actions\Procurement\CreatePurchaseBill;
use App\Actions\Procurement\CreateSupplier;
use App\Actions\Procurement\PostPurchaseBill;
use App\Actions\Procurement\RecordSupplierPayment;
use App\Actions\Procurement\ReverseSupplierPayment;
use App\Enums\MembershipStatus;
use App\Enums\SupplierPaymentMode;
use App\Enums\SupplierPaymentStatus;
use App\Enums\UserStatus;
use App\Models\Business;
use App\Models\BusinessMember;
use App\Models\Item;
use App\Models\PurchaseBill;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\SupplierLedgerEntry;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SupplierSubledgerAndPaymentTest extends TestCase
{
    use DatabaseTransactions;

    protected Business $business;

    protected User $owner;

    protected Supplier $supplier;

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

        $supplierAction = app(CreateSupplier::class);
        $this->supplier = $supplierAction->execute($this->owner, $this->business, [
            'name' => 'Metro Distributors',
            'code' => 'METRO-01',
        ]);

        $unit = Unit::factory()->create(['business_id' => $this->business->id]);
        $this->item = Item::factory()->create([
            'business_id' => $this->business->id,
            'unit_id' => $unit->id,
            'tax_rate' => 0,
        ]);
    }

    public function test_payment_reduces_outstanding_payable_and_updates_bill_status(): void
    {
        // 1. Post a bill for 1000.00
        $createBillAction = app(CreatePurchaseBill::class);
        $bill = $createBillAction->execute($this->owner, $this->business, [
            'supplier_id' => $this->supplier->id,
            'supplier_invoice_number' => 'BILL-1000',
            'lines' => [
                ['item_id' => $this->item->id, 'quantity' => 10, 'unit_cost' => 100.00, 'tax_rate' => 0],
            ],
        ]);
        $postBillAction = app(PostPurchaseBill::class);
        $bill = $postBillAction->execute($this->owner, $this->business, $bill);

        $this->assertEquals(1000.00, (float) $this->supplier->currentOutstanding());

        // 2. Pay 600.00 allocated to the bill
        $recordPaymentAction = app(RecordSupplierPayment::class);
        $payment = $recordPaymentAction->execute($this->owner, $this->business, [
            'supplier_id' => $this->supplier->id,
            'amount' => 600.00,
            'payment_mode' => SupplierPaymentMode::BankTransfer->value,
            'allocations' => [
                [
                    'purchase_bill_id' => $bill->id,
                    'amount' => 600.00,
                ],
            ],
        ]);

        $this->assertEquals(SupplierPaymentStatus::Posted, $payment->status);
        $this->assertEquals(600.00, (float) $payment->allocated_amount);
        $this->assertEquals(0.00, (float) $payment->advance_amount);

        // Bill should now be partially paid
        $bill->refresh();
        $this->assertEquals('partially_paid', $bill->status->value);
        $this->assertEquals(600.00, (float) $bill->paid_amount);

        // Supplier outstanding reduced to 400.00
        $this->assertEquals(400.00, (float) $this->supplier->currentOutstanding());

        // Subledger contains debit and credit entries
        $entries = SupplierLedgerEntry::where('supplier_id', $this->supplier->id)->get();
        $this->assertCount(2, $entries);
    }

    public function test_overpayment_creates_unallocated_advance_credit(): void
    {
        // 1. Post a bill for 500.00
        $createBillAction = app(CreatePurchaseBill::class);
        $bill = $createBillAction->execute($this->owner, $this->business, [
            'supplier_id' => $this->supplier->id,
            'supplier_invoice_number' => 'BILL-500',
            'lines' => [
                ['item_id' => $this->item->id, 'quantity' => 5, 'unit_cost' => 100.00, 'tax_rate' => 0],
            ],
        ]);
        $postBillAction = app(PostPurchaseBill::class);
        $bill = $postBillAction->execute($this->owner, $this->business, $bill);

        // 2. Pay 800.00 (500 allocated to bill, 300 surplus advance)
        $recordPaymentAction = app(RecordSupplierPayment::class);
        $payment = $recordPaymentAction->execute($this->owner, $this->business, [
            'supplier_id' => $this->supplier->id,
            'amount' => 800.00,
            'payment_mode' => SupplierPaymentMode::Upi->value,
            'allocations' => [
                [
                    'purchase_bill_id' => $bill->id,
                    'amount' => 500.00,
                ],
            ],
        ]);

        $this->assertEquals(500.00, (float) $payment->allocated_amount);
        $this->assertEquals(300.00, (float) $payment->advance_amount);

        // Bill is fully paid
        $bill->refresh();
        $this->assertEquals('paid', $bill->status->value);

        // Outstanding net balance is -300.00 (supplier advance credit owed to business)
        $this->assertEquals(-300.00, (float) $this->supplier->currentOutstanding());
    }

    public function test_allocating_advance_to_later_bill(): void
    {
        // 1. Record advance payment of 400.00 without any bill
        $recordPaymentAction = app(RecordSupplierPayment::class);
        $payment = $recordPaymentAction->execute($this->owner, $this->business, [
            'supplier_id' => $this->supplier->id,
            'amount' => 400.00,
            'payment_mode' => SupplierPaymentMode::BankTransfer->value,
        ]);

        $this->assertEquals(400.00, (float) $payment->advance_amount);

        // 2. Later, post a bill for 600.00
        $createBillAction = app(CreatePurchaseBill::class);
        $bill = $createBillAction->execute($this->owner, $this->business, [
            'supplier_id' => $this->supplier->id,
            'supplier_invoice_number' => 'LATER-BILL-600',
            'lines' => [
                ['item_id' => $this->item->id, 'quantity' => 6, 'unit_cost' => 100.00, 'tax_rate' => 0],
            ],
        ]);
        $postBillAction = app(PostPurchaseBill::class);
        $bill = $postBillAction->execute($this->owner, $this->business, $bill);

        // 3. Allocate 250.00 of the advance to the new bill
        $allocateAction = app(AllocateSupplierAdvance::class);
        $allocateAction->execute($this->owner, $this->business, $payment, $bill, 250.00);

        $payment->refresh();
        $this->assertEquals(250.00, (float) $payment->allocated_amount);
        $this->assertEquals(150.00, (float) $payment->advance_amount);

        $bill->refresh();
        $this->assertEquals(250.00, (float) $bill->paid_amount);
        $this->assertEquals('partially_paid', $bill->status->value);
    }

    public function test_payment_reversal_reopens_bills_and_records_reversal_entry(): void
    {
        $createBillAction = app(CreatePurchaseBill::class);
        $bill = $createBillAction->execute($this->owner, $this->business, [
            'supplier_id' => $this->supplier->id,
            'supplier_invoice_number' => 'BILL-REV-01',
            'lines' => [
                ['item_id' => $this->item->id, 'quantity' => 2, 'unit_cost' => 100.00, 'tax_rate' => 0],
            ],
        ]);
        $postBillAction = app(PostPurchaseBill::class);
        $bill = $postBillAction->execute($this->owner, $this->business, $bill);

        $recordPaymentAction = app(RecordSupplierPayment::class);
        $payment = $recordPaymentAction->execute($this->owner, $this->business, [
            'supplier_id' => $this->supplier->id,
            'amount' => 200.00,
            'payment_mode' => SupplierPaymentMode::Cash->value,
            'allocations' => [
                ['purchase_bill_id' => $bill->id, 'amount' => 200.00],
            ],
        ]);

        $this->assertEquals(0.00, (float) $this->supplier->currentOutstanding());

        // Reverse payment
        $reverseAction = app(ReverseSupplierPayment::class);
        $payment = $reverseAction->execute($this->owner, $this->business, $payment, 'Bounced cheque reversal');

        $this->assertEquals(SupplierPaymentStatus::Reversed, $payment->status);

        // Bill reopened to posted (unpaid)
        $bill->refresh();
        $this->assertEquals('posted', $bill->status->value);
        $this->assertEquals(0.00, (float) $bill->paid_amount);

        // Supplier outstanding restored to 200.00
        $this->assertEquals(200.00, (float) $this->supplier->currentOutstanding());

        // Cannot reverse twice
        $this->expectException(ValidationException::class);
        $reverseAction->execute($this->owner, $this->business, $payment, 'Second reversal attempt');
    }

    public function test_cross_supplier_bill_allocation_is_rejected(): void
    {
        $otherSupplier = Supplier::create([
            'business_id' => $this->business->id,
            'name' => 'Competitor Supplier',
            'code' => 'COMP-SUP',
            'status' => 'active',
        ]);

        $bill = PurchaseBill::create([
            'business_id' => $this->business->id,
            'supplier_id' => $otherSupplier->id, // Belongs to other supplier!
            'bill_number' => 'BILL-OTHER-01',
            'supplier_invoice_number' => 'INV-OTHER',
            'bill_date' => now(),
            'total_amount' => 500.00,
            'status' => 'posted',
            'created_by' => $this->owner->id,
        ]);

        $recordPaymentAction = app(RecordSupplierPayment::class);

        $this->expectException(ValidationException::class);

        // Allocating payment of $this->supplier to $otherSupplier's bill must fail
        $recordPaymentAction->execute($this->owner, $this->business, [
            'supplier_id' => $this->supplier->id,
            'amount' => 500.00,
            'payment_mode' => SupplierPaymentMode::Cash->value,
            'allocations' => [
                ['purchase_bill_id' => $bill->id, 'amount' => 500.00],
            ],
        ]);
    }
}
