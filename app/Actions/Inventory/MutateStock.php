<?php

namespace App\Actions\Inventory;

use App\Enums\InventoryMode;
use App\Enums\InventoryUnitStatus;
use App\Enums\StockMovementType;
use App\Models\Business;
use App\Models\InventoryBatch;
use App\Models\InventoryUnit;
use App\Models\Item;
use App\Models\StockBalance;
use App\Models\StockLocation;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MutateStock
{
    /**
     * Authoritatively and atomically mutate stock balance and record append-only stock movement.
     * Enforces tenancy, item modes, negative stock policy, and physical unit state machines.
     *
     * @return array{movement: StockMovement, balance: StockBalance}
     */
    public function execute(
        Business $business,
        Item $item,
        Warehouse $warehouse,
        StockMovementType $movementType,
        float $quantity,
        float $unitCost = 0.00,
        ?StockLocation $location = null,
        ?InventoryBatch $batch = null,
        ?InventoryUnit $unit = null,
        string $sourceType = 'manual',
        ?string $sourceId = null,
        ?string $sourceLineId = null,
        ?User $actor = null,
        ?CarbonInterface $occurredAt = null,
        ?string $reason = null,
        ?string $notes = null,
        ?string $reference = null
    ): array {
        // 1. Strict Tenant Isolation Invariant
        if ($item->business_id !== $business->id) {
            throw ValidationException::withMessages(['item' => ['Item does not belong to this business.']]);
        }
        if ($warehouse->business_id !== $business->id) {
            throw ValidationException::withMessages(['warehouse' => ['Warehouse does not belong to this business.']]);
        }
        if ($location !== null && ($location->business_id !== $business->id || $location->warehouse_id !== $warehouse->id)) {
            throw ValidationException::withMessages(['location' => ['Location does not belong to this warehouse.']]);
        }
        if ($batch !== null && ($batch->business_id !== $business->id || $batch->item_id !== $item->id)) {
            throw ValidationException::withMessages(['batch' => ['Batch does not belong to this item/business.']]);
        }
        if ($unit !== null && ($unit->business_id !== $business->id || $unit->item_id !== $item->id)) {
            throw ValidationException::withMessages(['unit' => ['Inventory unit does not belong to this item/business.']]);
        }

        // 2. Inactive item check (must be active for standard new operations)
        if (! $item->isActive() && ! in_array($movementType, [StockMovementType::Reversal, StockMovementType::StockAdjustment], true)) {
            throw ValidationException::withMessages(['item' => ["Item '{$item->name}' is inactive and cannot be used in new stock operations."]]);
        }

        // 3. Item Mode Tracking Invariants
        if ($item->inventory_mode === InventoryMode::Batch && $batch === null) {
            throw ValidationException::withMessages(['batch' => ["Batch-tracked item '{$item->name}' requires a valid batch identifier."]]);
        }

        if ($item->inventory_mode === InventoryMode::Serial) {
            if ($unit === null || empty($unit->serial_number)) {
                throw ValidationException::withMessages(['unit' => ["Serial-tracked item '{$item->name}' requires a physical unit with serial number."]]);
            }
            if (abs($quantity) != 1.0) {
                throw ValidationException::withMessages(['quantity' => ['Serial-tracked items must be transacted exactly one unit at a time.']]);
            }
        }

        if ($item->inventory_mode === InventoryMode::Imei) {
            if ($unit === null || empty($unit->imei1)) {
                throw ValidationException::withMessages(['unit' => ["IMEI-tracked item '{$item->name}' requires a physical unit with IMEI."]]);
            }
            if (abs($quantity) != 1.0) {
                throw ValidationException::withMessages(['quantity' => ['IMEI-tracked items must be transacted exactly one unit at a time.']]);
            }
        }

        // Determine signed quantity: positive for incoming, negative for outgoing
        $signedQuantity = $quantity;

        return DB::transaction(function () use (
            $business, $item, $warehouse, $movementType, $signedQuantity, $unitCost,
            $location, $batch, $unit, $sourceType, $sourceId, $sourceLineId,
            $actor, $occurredAt, $reason, $notes, $reference
        ) {
            // 4. Concurrency-Safe Balance Retrieval with Row Lock
            $balance = StockBalance::where('business_id', $business->id)
                ->where('item_id', $item->id)
                ->where('warehouse_id', $warehouse->id)
                ->where('location_id', $location?->id)
                ->where('batch_id', $batch?->id)
                ->lockForUpdate()
                ->first();

            if (! $balance) {
                $balance = StockBalance::create([
                    'business_id' => $business->id,
                    'item_id' => $item->id,
                    'warehouse_id' => $warehouse->id,
                    'location_id' => $location?->id,
                    'batch_id' => $batch?->id,
                    'quantity' => 0.0000,
                ]);

                $balance = StockBalance::where('id', $balance->id)->lockForUpdate()->first();
            }

            $currentQty = (float) $balance->quantity;
            $newQty = round($currentQty + $signedQuantity, 4);

            // 5. Negative Stock Policy Validation
            if ($signedQuantity < 0) {
                // Batch inventory must NEVER be negative
                if ($item->inventory_mode === InventoryMode::Batch && $newQty < 0) {
                    throw ValidationException::withMessages([
                        'quantity' => ["Batch '{$batch->batch_number}' has insufficient available stock. Available: {$currentQty}, requested: ".abs($signedQuantity)],
                    ]);
                }

                // Serial & IMEI inventory must NEVER be negative or fictional
                if (in_array($item->inventory_mode, [InventoryMode::Serial, InventoryMode::Imei], true)) {
                    if ($newQty < 0) {
                        throw ValidationException::withMessages([
                            'quantity' => ["Insufficient physical stock in warehouse for serialized unit. Available: {$currentQty}"],
                        ]);
                    }
                    if ($unit && ! $unit->isAvailable() && $movementType !== StockMovementType::Reversal) {
                        throw ValidationException::withMessages([
                            'unit' => ["Physical unit is in state '{$unit->status->value}' and cannot be consumed or transferred."],
                        ]);
                    }
                }

                // Normal / Accessory inventory: check tenant allow_negative_stock policy
                if (in_array($item->inventory_mode, [InventoryMode::Normal, InventoryMode::Accessory], true)) {
                    if ($newQty < 0 && ! $business->allow_negative_stock) {
                        throw ValidationException::withMessages([
                            'quantity' => ["Negative stock is disabled. Available stock for '{$item->name}' is {$currentQty}, cannot deduct ".abs($signedQuantity)],
                        ]);
                    }
                }
            }

            // 6. Write Immutable Movement Record
            $movement = StockMovement::create([
                'business_id' => $business->id,
                'item_id' => $item->id,
                'warehouse_id' => $warehouse->id,
                'location_id' => $location?->id,
                'batch_id' => $batch?->id,
                'inventory_unit_id' => $unit?->id,
                'movement_type' => $movementType,
                'quantity' => $signedQuantity,
                'unit_cost' => $unitCost,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'source_line_id' => $sourceLineId,
                'actor_id' => $actor?->id,
                'occurred_at' => $occurredAt ?? now(),
                'reason' => $reason,
                'notes' => $notes,
                'reference' => $reference,
            ]);

            // 7. Update Balance Projection
            $balance->update(['quantity' => $newQty]);

            // 8. Update Serial/IMEI Unit State
            if ($unit) {
                if ($signedQuantity > 0) {
                    $unit->update([
                        'warehouse_id' => $warehouse->id,
                        'location_id' => $location?->id,
                        'status' => InventoryUnitStatus::Available,
                        'cost_price' => $unitCost > 0 ? $unitCost : $unit->cost_price,
                    ]);
                } else {
                    $newUnitStatus = match ($movementType) {
                        StockMovementType::TransferOut => InventoryUnitStatus::InTransit,
                        StockMovementType::Damage => InventoryUnitStatus::Damaged,
                        StockMovementType::Loss => InventoryUnitStatus::Lost,
                        default => InventoryUnitStatus::Consumed,
                    };
                    $unit->update(['status' => $newUnitStatus]);
                }
            }

            return [
                'movement' => $movement,
                'balance' => $balance,
            ];
        });
    }
}
