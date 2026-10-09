<?php

namespace App\Actions\Inventory;

use App\Enums\StockAdjustmentStatus;
use App\Enums\StockMovementType;
use App\Models\AuditLog;
use App\Models\Business;
use App\Models\InventoryBatch;
use App\Models\InventoryUnit;
use App\Models\Item;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentLine;
use App\Models\StockBalance;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdjustStock
{
    public function __construct(
        protected MutateStock $mutateStock
    ) {}

    /**
     * @param array{
     *     warehouse_id: string,
     *     adjustment_date?: ?string,
     *     reason: string,
     *     notes?: ?string,
     *     lines: array<array{
     *         item_id: string,
     *         batch_id?: ?string,
     *         inventory_unit_id?: ?string,
     *         counted_quantity: numeric,
     *         notes?: ?string
     *     }>
     * } $data
     */
    public function execute(User $actor, Business $business, array $data): StockAdjustment
    {
        $warehouse = Warehouse::where('id', $data['warehouse_id'])->where('business_id', $business->id)->firstOrFail();
        $reason = trim($data['reason']);
        if (empty($reason)) {
            throw ValidationException::withMessages(['reason' => ['Adjustment reason is mandatory.']]);
        }

        $linesData = (array) ($data['lines'] ?? []);
        if (empty($linesData)) {
            throw ValidationException::withMessages(['lines' => ['At least one line item must be included in the adjustment.']]);
        }

        $adjNumber = 'ADJ-'.strtoupper(bin2hex(random_bytes(4)));

        return DB::transaction(function () use ($actor, $business, $warehouse, $reason, $adjNumber, $linesData, $data) {
            $adjustment = StockAdjustment::create([
                'business_id' => $business->id,
                'warehouse_id' => $warehouse->id,
                'adjustment_number' => $adjNumber,
                'adjustment_date' => ! empty($data['adjustment_date']) ? Carbon::parse($data['adjustment_date']) : now()->toDateString(),
                'reason' => $reason,
                'notes' => $data['notes'] ?? null,
                'status' => StockAdjustmentStatus::Draft,
                'created_by' => $actor->id,
            ]);

            foreach ($linesData as $line) {
                $item = Item::where('id', $line['item_id'])->where('business_id', $business->id)->firstOrFail();
                $batch = ! empty($line['batch_id']) ? InventoryBatch::where('id', $line['batch_id'])->where('business_id', $business->id)->first() : null;
                $unit = ! empty($line['inventory_unit_id']) ? InventoryUnit::where('id', $line['inventory_unit_id'])->where('business_id', $business->id)->first() : null;

                // Find current system quantity
                $balance = StockBalance::where('business_id', $business->id)
                    ->where('item_id', $item->id)
                    ->where('warehouse_id', $warehouse->id)
                    ->where('batch_id', $batch?->id)
                    ->first();

                $systemQty = $balance ? (float) $balance->quantity : 0.0000;
                $countedQty = (float) $line['counted_quantity'];
                $adjustmentQty = round($countedQty - $systemQty, 4);

                $adjLine = StockAdjustmentLine::create([
                    'stock_adjustment_id' => $adjustment->id,
                    'item_id' => $item->id,
                    'batch_id' => $batch?->id,
                    'inventory_unit_id' => $unit?->id,
                    'system_quantity' => $systemQty,
                    'counted_quantity' => $countedQty,
                    'adjustment_quantity' => $adjustmentQty,
                    'unit_cost' => (float) $item->cost_price,
                    'notes' => $line['notes'] ?? null,
                ]);

                // Post the adjustment movement if there is a discrepancy
                if ($adjustmentQty != 0.0) {
                    $this->mutateStock->execute(
                        business: $business,
                        item: $item,
                        warehouse: $warehouse,
                        movementType: StockMovementType::StockAdjustment,
                        quantity: $adjustmentQty,
                        unitCost: (float) $item->cost_price,
                        batch: $batch,
                        unit: $unit,
                        sourceType: 'stock_adjustment',
                        sourceId: $adjustment->id,
                        sourceLineId: $adjLine->id,
                        actor: $actor,
                        reason: $reason,
                        notes: "Adjustment {$adjNumber}: system={$systemQty}, counted={$countedQty}"
                    );
                }
            }

            $adjustment->update([
                'status' => StockAdjustmentStatus::Posted,
                'posted_by' => $actor->id,
                'posted_at' => now(),
            ]);

            AuditLog::create([
                'business_id' => $business->id,
                'user_id' => $actor->id,
                'action' => 'stock_adjustment.posted',
                'auditable_type' => StockAdjustment::class,
                'auditable_id' => $adjustment->id,
                'payload' => [
                    'adjustment_number' => $adjustment->adjustment_number,
                    'warehouse_id' => $warehouse->id,
                    'lines_count' => count($linesData),
                ],
            ]);

            return $adjustment->load('lines.item');
        });
    }
}
