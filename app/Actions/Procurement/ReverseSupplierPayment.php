<?php

namespace App\Actions\Procurement;

use App\Enums\PurchaseBillStatus;
use App\Enums\SupplierLedgerEntryType;
use App\Enums\SupplierPaymentStatus;
use App\Models\AuditLog;
use App\Models\Business;
use App\Models\SupplierLedgerEntry;
use App\Models\SupplierPayment;
use App\Models\User;
use App\Services\Auth\AuthorizationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReverseSupplierPayment
{
    public function __construct(
        protected AuthorizationService $authorizationService
    ) {}

    public function execute(User $actor, Business $business, SupplierPayment $payment, string $reason = 'Reversal requested'): SupplierPayment
    {
        if ($payment->business_id !== $business->id) {
            throw ValidationException::withMessages(['payment' => ['Unauthorized payment context.']]);
        }

        // Authorize actor possesses suppliers.payments.post permission
        if ($this->authorizationService->cannot($actor, 'suppliers.payments.post', $business)) {
            throw ValidationException::withMessages(['authorization' => ['You do not have permission to reverse supplier payments.']]);
        }

        if ($payment->status === SupplierPaymentStatus::Reversed) {
            throw ValidationException::withMessages(['status' => ['This payment has already been reversed.']]);
        }

        return DB::transaction(function () use ($actor, $business, $payment, $reason) {
            // Restore bill paid amounts
            foreach ($payment->allocations as $alloc) {
                $bill = $alloc->bill;
                $newPaid = max(0.0, round((float) $bill->paid_amount - (float) $alloc->amount, 2));
                $newStatus = ($newPaid > 0) ? PurchaseBillStatus::PartiallyPaid : PurchaseBillStatus::Posted;

                $bill->update([
                    'paid_amount' => $newPaid,
                    'status' => $newStatus,
                ]);
            }

            // Record reversal entry in supplier subledger (+amount increases payable back)
            $entry = SupplierLedgerEntry::create([
                'business_id' => $business->id,
                'supplier_id' => $payment->supplier_id,
                'entry_type' => SupplierLedgerEntryType::PaymentReversed,
                'amount' => $payment->amount,
                'running_balance' => (float) $payment->supplier->currentOutstanding() + (float) $payment->amount,
                'source_type' => 'supplier_payment_reversal',
                'source_id' => $payment->id,
                'source_reference' => $payment->payment_number,
                'posted_at' => now(),
                'actor_id' => $actor->id,
                'notes' => "Reversal of payment #{$payment->payment_number}. Reason: {$reason}",
            ]);

            $payment->update([
                'status' => SupplierPaymentStatus::Reversed,
                'reversed_by' => $actor->id,
                'reversed_at' => now(),
                'notes' => trim(($payment->notes ? $payment->notes."\n" : '')."Reversed: {$reason}"),
            ]);

            AuditLog::create([
                'business_id' => $business->id,
                'user_id' => $actor->id,
                'action' => 'supplier_payment.reversed',
                'auditable_type' => SupplierPayment::class,
                'auditable_id' => $payment->id,
                'payload' => [
                    'payment_number' => $payment->payment_number,
                    'reason' => $reason,
                ],
            ]);

            return $payment->fresh(['allocations.bill', 'supplier']);
        });
    }
}
