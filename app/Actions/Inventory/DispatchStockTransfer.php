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

class DispatchStockTransfer
{
    public function __construct(
        protected MutateStock $mutateStock
    ) {}

    public function execute(User $actor, Business $business, StockTransfer $transfer): StockTransfer
    {
        if ($transfer->business_id !== $business->id) {
            throw ValidationException::withMessages(['transfer' => ['Unauthorized transfer context.']]);
        }

        if ($transfer->status !== StockTransferStatus::Draft) {
            throw ValidationException::withMessages(['status' => ["Only draft transfers can be dispatched (current: {$transfer->status->value})."]]);
        }

        return DB::transaction(function () use ($actor, $business, $transfer) {
            $source = $transfer->sourceWarehouse;

            foreach ($transfer->lines as $line) {
                $item = $line->item;
                $qty = (float) $line->quantity_dispatched;

                // Mutate stock out of source warehouse (negative quantity)
                $this->mutateStock->execute(
                    business: $business,
                    item: $item,
                    warehouse: $source,
                    movementType: StockMovementType::TransferOut,
                    quantity: -$qty,
                    unitCost: (float) $item->cost_price,
                    batch: $line->batch,
                    unit: $line->unit,
                    sourceType: 'stock_transfer',
                    sourceId: $transfer->id,
                    sourceLineId: $line->id,
                    actor: $actor,
                    notes: "Dispatched transfer {$transfer->transfer_number} to {$transfer->destinationWarehouse->name}"
                );
            }

            $transfer->update([
                'status' => StockTransferStatus::Dispatched,
                'dispatched_by' => $actor->id,
                'dispatched_at' => now(),
            ]);

            AuditLog::create([
                'business_id' => $business->id,
                'user_id' => $actor->id,
                'action' => 'stock_transfer.dispatched',
                'auditable_type' => StockTransfer::class,
                'auditable_id' => $transfer->id,
                'payload' => [
                    'transfer_number' => $transfer->transfer_number,
                    'dispatched_at' => $transfer->dispatched_at->toIso8601String(),
                ],
            ]);

            return $transfer->fresh(['lines.item', 'sourceWarehouse', 'destinationWarehouse']);
        });
    }
}
