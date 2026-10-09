<?php

namespace App\Actions\Procurement;

use App\Enums\PurchaseBillStatus;
use App\Enums\SupplierLedgerEntryType;
use App\Models\AuditLog;
use App\Models\Business;
use App\Models\PurchaseBill;
use App\Models\SupplierLedgerEntry;
use App\Models\SupplierPayment;
use App\Models\SupplierPaymentAllocation;
use App\Models\User;
use App\Services\Auth\AuthorizationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AllocateSupplierAdvance
{
    public function __construct(
        protected AuthorizationService $authorizationService
    ) {}

    public function execute(User $actor, Business $business, SupplierPayment $payment, PurchaseBill $bill, float $amount): SupplierPaymentAllocation
    {
        if ($payment->business_id !== $business->id || $bill->business_id !== $business->id) {
            throw ValidationException::withMessages(['tenant' => ['Unauthorized tenant boundary.']]);
        }

        if ($this->authorizationService->cannot($actor, 'suppliers.payments.post', $business)) {
            throw ValidationException::withMessages(['authorization' => ['You do not have permission to allocate supplier advances.']]);
        }

        if ($payment->supplier_id !== $bill->supplier_id) {
            throw ValidationException::withMessages(['supplier' => ['Payment advance and bill must belong to the same supplier.']]);
        }

        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => ['Allocation amount must be greater than zero.']]);
        }

        $availableAdvance = (float) $payment->advance_amount;
        if ($amount > $availableAdvance) {
            throw ValidationException::withMessages(['amount' => ["Requested allocation of ₹{$amount} exceeds unapplied advance of ₹{$availableAdvance}."]]);
        }

        $outstanding = $bill->outstandingAmount();
        if ($amount > $outstanding) {
            throw ValidationException::withMessages(['amount' => ["Requested allocation of ₹{$amount} exceeds bill outstanding balance of ₹{$outstanding}."]]);
        }

        return DB::transaction(function () use ($actor, $business, $payment, $bill, $amount) {
            $allocation = SupplierPaymentAllocation::create([
                'business_id' => $business->id,
                'supplier_payment_id' => $payment->id,
                'purchase_bill_id' => $bill->id,
                'amount' => $amount,
            ]);

            $payment->update([
                'allocated_amount' => round((float) $payment->allocated_amount + $amount, 2),
                'advance_amount' => round((float) $payment->advance_amount - $amount, 2),
            ]);

            $newPaid = round((float) $bill->paid_amount + $amount, 2);
            $newStatus = ($newPaid >= (float) $bill->total_amount) ? PurchaseBillStatus::Paid : PurchaseBillStatus::PartiallyPaid;

            $bill->update([
                'paid_amount' => $newPaid,
                'status' => $newStatus,
            ]);

            // Note: Overall supplier debt is unchanged because the payment was already recorded in subledger,
            // but we record the advance allocation tracking entry.
            SupplierLedgerEntry::create([
                'business_id' => $business->id,
                'supplier_id' => $payment->supplier_id,
                'entry_type' => SupplierLedgerEntryType::AdvanceAllocated,
                'amount' => 0.00,
                'running_balance' => (float) $payment->supplier->currentOutstanding(),
                'source_type' => 'supplier_advance_allocation',
                'source_id' => $allocation->id,
                'source_reference' => "{$payment->payment_number} -> {$bill->bill_number}",
                'posted_at' => now(),
                'actor_id' => $actor->id,
                'notes' => "Allocated ₹{$amount} advance to bill #{$bill->bill_number}",
            ]);

            AuditLog::create([
                'business_id' => $business->id,
                'user_id' => $actor->id,
                'action' => 'supplier_advance.allocated',
                'auditable_type' => SupplierPaymentAllocation::class,
                'auditable_id' => $allocation->id,
                'payload' => [
                    'payment_number' => $payment->payment_number,
                    'bill_number' => $bill->bill_number,
                    'amount' => $amount,
                ],
            ]);

            return $allocation;
        });
    }
}
