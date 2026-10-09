<?php

namespace App\Actions\Procurement;

use App\Enums\PurchaseBillStatus;
use App\Enums\SupplierLedgerEntryType;
use App\Models\AuditLog;
use App\Models\Business;
use App\Models\PurchaseBill;
use App\Models\PurchaseOrderLine;
use App\Models\SupplierLedgerEntry;
use App\Models\User;
use App\Services\Auth\AuthorizationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PostPurchaseBill
{
    public function __construct(
        protected AuthorizationService $authorizationService
    ) {}

    public function execute(User $actor, Business $business, PurchaseBill $bill): PurchaseBill
    {
        if ($bill->business_id !== $business->id) {
            throw ValidationException::withMessages(['bill' => ['Unauthorized purchase bill context.']]);
        }

        // Authorize actor possesses purchases.bills.post permission
        if ($this->authorizationService->cannot($actor, 'purchases.bills.post', $business)) {
            throw ValidationException::withMessages(['authorization' => ['You do not have permission to post purchase bills.']]);
        }

        if ($bill->status !== PurchaseBillStatus::Draft) {
            throw ValidationException::withMessages(['status' => ["Only draft bills can be posted (current: {$bill->status->value})."]]);
        }

        return DB::transaction(function () use ($actor, $business, $bill) {
            // 1. Post to Supplier Subledger (Credit/Liability increase: positive amount)
            SupplierLedgerEntry::create([
                'business_id' => $business->id,
                'supplier_id' => $bill->supplier_id,
                'entry_type' => SupplierLedgerEntryType::BillPosted,
                'amount' => $bill->total_amount,
                'running_balance' => (float) $bill->supplier->currentOutstanding() + (float) $bill->total_amount,
                'source_type' => 'purchase_bill',
                'source_id' => $bill->id,
                'source_reference' => $bill->bill_number,
                'posted_at' => now(),
                'actor_id' => $actor->id,
                'notes' => "Invoice #{$bill->supplier_invoice_number}",
            ]);

            // 2. Update Bill Status
            $bill->update([
                'status' => PurchaseBillStatus::Posted,
                'posted_by' => $actor->id,
                'posted_at' => now(),
            ]);

            // 3. Update Billed Quantities on PO Lines if linked
            if ($bill->purchase_order_id) {
                foreach ($bill->lines as $bLine) {
                    $poLine = PurchaseOrderLine::where('purchase_order_id', $bill->purchase_order_id)
                        ->where('item_id', $bLine->item_id)
                        ->first();

                    if ($poLine) {
                        $poLine->increment('billed_quantity', (float) $bLine->quantity);
                    }
                }
            }

            AuditLog::create([
                'business_id' => $business->id,
                'user_id' => $actor->id,
                'action' => 'purchase_bill.posted',
                'auditable_type' => PurchaseBill::class,
                'auditable_id' => $bill->id,
                'payload' => [
                    'bill_number' => $bill->bill_number,
                    'total_amount' => $bill->total_amount,
                    'supplier_id' => $bill->supplier_id,
                ],
            ]);

            return $bill->fresh(['lines.item', 'supplier']);
        });
    }
}
