<?php

namespace App\Actions\Procurement;

use App\Enums\PurchaseBillStatus;
use App\Enums\SupplierLedgerEntryType;
use App\Enums\SupplierPaymentMode;
use App\Enums\SupplierPaymentStatus;
use App\Models\AuditLog;
use App\Models\Business;
use App\Models\PurchaseBill;
use App\Models\Supplier;
use App\Models\SupplierLedgerEntry;
use App\Models\SupplierPayment;
use App\Models\SupplierPaymentAllocation;
use App\Models\User;
use App\Services\Auth\AuthorizationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordSupplierPayment
{
    public function __construct(
        protected AuthorizationService $authorizationService
    ) {}

    /**
     * @param array{
     *     supplier_id: string,
     *     amount: numeric,
     *     payment_date?: ?string,
     *     payment_mode: string|SupplierPaymentMode,
     *     reference_number?: ?string,
     *     notes?: ?string,
     *     idempotency_key?: ?string,
     *     allocations?: array<array{purchase_bill_id: string, amount: numeric}>
     * } $data
     */
    public function execute(User $actor, Business $business, array $data): SupplierPayment
    {
        // Authorize actor possesses suppliers.payments.post permission
        if ($this->authorizationService->cannot($actor, 'suppliers.payments.post', $business)) {
            throw ValidationException::withMessages(['authorization' => ['You do not have permission to post supplier payments.']]);
        }

        $supplier = Supplier::where('id', $data['supplier_id'])->where('business_id', $business->id)->firstOrFail();
        $amount = (float) $data['amount'];
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => ['Payment amount must be greater than zero.']]);
        }

        $idempotencyKey = filled($data['idempotency_key'] ?? null) ? trim($data['idempotency_key']) : null;
        if ($idempotencyKey !== null && SupplierPayment::where('business_id', $business->id)->where('idempotency_key', $idempotencyKey)->exists()) {
            throw ValidationException::withMessages(['idempotency_key' => ['A payment with this idempotency reference has already been processed.']]);
        }

        $paymentNumber = 'PAY-'.strtoupper(bin2hex(random_bytes(4)));
        $paymentMode = $data['payment_mode'] instanceof SupplierPaymentMode ? $data['payment_mode'] : SupplierPaymentMode::from($data['payment_mode']);

        $allocationsData = (array) ($data['allocations'] ?? []);

        return DB::transaction(function () use ($actor, $business, $supplier, $paymentNumber, $amount, $paymentMode, $idempotencyKey, $allocationsData, $data) {
            $totalAllocated = 0.00;
            $validatedAllocations = [];

            foreach ($allocationsData as $alloc) {
                $bill = PurchaseBill::where('id', $alloc['purchase_bill_id'])->where('business_id', $business->id)->firstOrFail();

                // Cross-supplier check
                if ($bill->supplier_id !== $supplier->id) {
                    throw ValidationException::withMessages(['allocations' => ["Bill '{$bill->bill_number}' belongs to a different supplier."]]);
                }

                if (! in_array($bill->status, [PurchaseBillStatus::Posted, PurchaseBillStatus::PartiallyPaid], true)) {
                    throw ValidationException::withMessages(['allocations' => ["Bill '{$bill->bill_number}' is not in an outstanding posted state."]]);
                }

                $allocAmount = (float) $alloc['amount'];
                if ($allocAmount <= 0) {
                    throw ValidationException::withMessages(['allocations' => ['Allocation amount must be greater than zero.']]);
                }

                $outstanding = $bill->outstandingAmount();
                if ($allocAmount > $outstanding) {
                    throw ValidationException::withMessages(['allocations' => ["Allocation of ₹{$allocAmount} exceeds bill '{$bill->bill_number}' outstanding amount of ₹{$outstanding}."]]);
                }

                $totalAllocated += $allocAmount;
                $validatedAllocations[] = [
                    'bill' => $bill,
                    'amount' => $allocAmount,
                ];
            }

            if ($totalAllocated > $amount) {
                throw ValidationException::withMessages(['allocations' => ['Total allocated amount cannot exceed payment amount.']]);
            }

            $advanceAmount = round($amount - $totalAllocated, 2);

            $payment = SupplierPayment::create([
                'business_id' => $business->id,
                'supplier_id' => $supplier->id,
                'payment_number' => $paymentNumber,
                'payment_date' => ! empty($data['payment_date']) ? Carbon::parse($data['payment_date']) : now()->toDateString(),
                'amount' => $amount,
                'payment_mode' => $paymentMode,
                'reference_number' => filled($data['reference_number'] ?? null) ? trim($data['reference_number']) : null,
                'allocated_amount' => $totalAllocated,
                'advance_amount' => $advanceAmount,
                'status' => SupplierPaymentStatus::Posted,
                'notes' => $data['notes'] ?? null,
                'posted_by' => $actor->id,
                'posted_at' => now(),
                'idempotency_key' => $idempotencyKey,
            ]);

            // Save allocations and update bills
            foreach ($validatedAllocations as $va) {
                $b = $va['bill'];
                $aAmt = $va['amount'];

                SupplierPaymentAllocation::create([
                    'business_id' => $business->id,
                    'supplier_payment_id' => $payment->id,
                    'purchase_bill_id' => $b->id,
                    'amount' => $aAmt,
                ]);

                $newPaid = round((float) $b->paid_amount + $aAmt, 2);
                $newStatus = ($newPaid >= (float) $b->total_amount) ? PurchaseBillStatus::Paid : PurchaseBillStatus::PartiallyPaid;

                $b->update([
                    'paid_amount' => $newPaid,
                    'status' => $newStatus,
                ]);
            }

            // Post to Supplier Subledger: Payment reduces liability (negative amount)
            SupplierLedgerEntry::create([
                'business_id' => $business->id,
                'supplier_id' => $supplier->id,
                'entry_type' => SupplierLedgerEntryType::PaymentPosted,
                'amount' => -$amount,
                'running_balance' => (float) $supplier->currentOutstanding() - $amount,
                'source_type' => 'supplier_payment',
                'source_id' => $payment->id,
                'source_reference' => $payment->payment_number,
                'posted_at' => now(),
                'actor_id' => $actor->id,
                'notes' => "Payment mode: {$paymentMode->label()}".($advanceAmount > 0 ? " (includes advance ₹{$advanceAmount})" : ''),
            ]);

            AuditLog::create([
                'business_id' => $business->id,
                'user_id' => $actor->id,
                'action' => 'supplier_payment.posted',
                'auditable_type' => SupplierPayment::class,
                'auditable_id' => $payment->id,
                'payload' => [
                    'payment_number' => $payment->payment_number,
                    'amount' => $payment->amount,
                    'allocated_amount' => $payment->allocated_amount,
                    'advance_amount' => $payment->advance_amount,
                ],
            ]);

            return $payment->load(['allocations.bill', 'supplier']);
        });
    }
}
