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

class ReceiveStockTransfer
{
    public function __construct(
        protected MutateStock $mutateStock
    ) {}

    /**
     * @param array<string, array{
     *     received_quantity: numeric,
     *     damaged_quantity?: numeric,
     *     lost_quantity?: numeric
     * }> $receipts Keyed by line_id
     */
    public function execute(User $actor, Business $business, StockTransfer $transfer, array $receipts): StockTransfer
    {
        if ($transfer->business_id !== $business->id) {
            throw ValidationException::withMessages(['transfer' => ['Unauthorized transfer context.']]);
        }

        if (! in_array($transfer->status, [StockTransferStatus::Dispatched, StockTransferStatus::PartiallyReceived], true)) {
            throw ValidationException::withMessages(['status' => ["Transfer is not in a dispatch state eligible for receiving (current: {$transfer->status->value})."]]);
        }

        return DB::transaction(function () use ($actor, $business, $transfer, $receipts) {
            $destination = $transfer->destinationWarehouse;
            $allCompleted = true;

            foreach ($transfer->lines as $line) {
                $lineReceipt = $receipts[$line->id] ?? null;

                $receivedNow = $lineReceipt ? (float) ($lineReceipt['received_quantity'] ?? 0) : 0.0;
                $damagedNow = $lineReceipt ? (float) ($lineReceipt['damaged_quantity'] ?? 0) : 0.0;
                $lostNow = $lineReceipt ? (float) ($lineReceipt['lost_quantity'] ?? 0) : 0.0;

                $totalNewProcessed = $receivedNow + $damagedNow + $lostNow;
                $previouslyProcessed = (float) $line->quantity_received + (float) $line->quantity_damaged + (float) $line->quantity_lost;
                $dispatchedQty = (float) $line->quantity_dispatched;

                if ($previouslyProcessed + $totalNewProcessed > $dispatchedQty) {
                    throw ValidationException::withMessages([
                        'receipts' => ["Received quantity for line '{$line->item->name}' exceeds outstanding dispatched quantity."],
                    ]);
                }

                // If good items received, add to destination warehouse
                if ($receivedNow > 0) {
                    $this->mutateStock->execute(
                        business: $business,
                        item: $line->item,
                        warehouse: $destination,
                        movementType: StockMovementType::TransferIn,
                        quantity: $receivedNow,
                        unitCost: (float) $line->item->cost_price,
                        batch: $line->batch,
                        unit: $line->unit,
                        sourceType: 'stock_transfer',
                        sourceId: $transfer->id,
                        sourceLineId: $line->id,
                        actor: $actor,
                        notes: "Received transfer {$transfer->transfer_number} from {$transfer->sourceWarehouse->name}"
                    );
                }

                // If damaged items received, record damage movement
                if ($damagedNow > 0) {
                    $this->mutateStock->execute(
                        business: $business,
                        item: $line->item,
                        warehouse: $destination,
                        movementType: StockMovementType::Damage,
                        quantity: -$damagedNow,
                        unitCost: (float) $line->item->cost_price,
                        batch: $line->batch,
                        unit: $line->unit,
                        sourceType: 'stock_transfer',
                        sourceId: $transfer->id,
                        sourceLineId: $line->id,
                        actor: $actor,
                        notes: "Damaged in transit during transfer {$transfer->transfer_number}"
                    );
                }

                // If lost items, record loss movement
                if ($lostNow > 0) {
                    $this->mutateStock->execute(
                        business: $business,
                        item: $line->item,
                        warehouse: $destination,
                        movementType: StockMovementType::Loss,
                        quantity: -$lostNow,
                        unitCost: (float) $line->item->cost_price,
                        batch: $line->batch,
                        unit: $line->unit,
                        sourceType: 'stock_transfer',
                        sourceId: $transfer->id,
                        sourceLineId: $line->id,
                        actor: $actor,
                        notes: "Lost in transit during transfer {$transfer->transfer_number}"
                    );
                }

                $newTotalReceived = (float) $line->quantity_received + $receivedNow;
                $newTotalDamaged = (float) $line->quantity_damaged + $damagedNow;
                $newTotalLost = (float) $line->quantity_lost + $lostNow;

                $line->update([
                    'quantity_received' => $newTotalReceived,
                    'quantity_damaged' => $newTotalDamaged,
                    'quantity_lost' => $newTotalLost,
                ]);

                if (($newTotalReceived + $newTotalDamaged + $newTotalLost) < $dispatchedQty) {
                    $allCompleted = false;
                }
            }

            $newStatus = $allCompleted ? StockTransferStatus::Received : StockTransferStatus::PartiallyReceived;

            $transfer->update([
                'status' => $newStatus,
                'received_by' => $actor->id,
                'received_at' => now(),
            ]);

            AuditLog::create([
                'business_id' => $business->id,
                'user_id' => $actor->id,
                'action' => 'stock_transfer.received',
                'auditable_type' => StockTransfer::class,
                'auditable_id' => $transfer->id,
                'payload' => [
                    'transfer_number' => $transfer->transfer_number,
                    'status' => $newStatus->value,
                ],
            ]);

            return $transfer->fresh(['lines.item', 'sourceWarehouse', 'destinationWarehouse']);
        });
    }
}
