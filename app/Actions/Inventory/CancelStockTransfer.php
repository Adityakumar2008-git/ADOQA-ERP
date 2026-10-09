<?php

namespace App\Actions\Inventory;

use App\Enums\StockMovementType;
use App\Enums\StockTransferStatus;
use App\Models\AuditLog;
use App\Models\Business;
use App\Models\StockTransfer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelStockTransfer
{
    public function __construct(
        protected MutateStock $mutateStock
    ) {}

    public function execute(User $actor, Business $business, StockTransfer $transfer, string $reason = 'Cancelled by user'): StockTransfer
    {
        if ($transfer->business_id !== $business->id) {
            throw ValidationException::withMessages(['transfer' => ['Unauthorized transfer context.']]);
        }

        if (in_array($transfer->status, [StockTransferStatus::Received, StockTransferStatus::Cancelled], true)) {
            throw ValidationException::withMessages(['status' => ["Cannot cancel a transfer in status '{$transfer->status->value}'."]]);
        }

        return DB::transaction(function () use ($actor, $business, $transfer, $reason) {
            // If already dispatched, we must reverse the in-transit goods back to source warehouse
            if (in_array($transfer->status, [StockTransferStatus::Dispatched, StockTransferStatus::PartiallyReceived], true)) {
                $source = $transfer->sourceWarehouse;

                foreach ($transfer->lines as $line) {
                    $unreceivedQty = (float) $line->quantity_dispatched - (float) $line->quantity_received - (float) $line->quantity_damaged - (float) $line->quantity_lost;

                    if ($unreceivedQty > 0) {
                        $this->mutateStock->execute(
                            business: $business,
                            item: $line->item,
                            warehouse: $source,
                            movementType: StockMovementType::Reversal,
                            quantity: $unreceivedQty,
                            unitCost: (float) $line->item->cost_price,
                            batch: $line->batch,
                            unit: $line->unit,
                            sourceType: 'stock_transfer_cancellation',
                            sourceId: $transfer->id,
                            sourceLineId: $line->id,
                            actor: $actor,
                            reason: $reason,
                            notes: "Reversal of cancelled transfer {$transfer->transfer_number} back to {$source->name}"
                        );
                    }
                }
            }

            $transfer->update([
                'status' => StockTransferStatus::Cancelled,
                'notes' => trim(($transfer->notes ? $transfer->notes."\n" : '')."Cancelled: {$reason}"),
            ]);

            AuditLog::create([
                'business_id' => $business->id,
                'user_id' => $actor->id,
                'action' => 'stock_transfer.cancelled',
                'auditable_type' => StockTransfer::class,
                'auditable_id' => $transfer->id,
                'payload' => [
                    'transfer_number' => $transfer->transfer_number,
                    'reason' => $reason,
                ],
            ]);

            return $transfer->fresh(['lines.item', 'sourceWarehouse', 'destinationWarehouse']);
        });
    }
}
