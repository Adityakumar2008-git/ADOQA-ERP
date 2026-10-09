<?php

namespace App\Actions\Inventory;

use App\Enums\StockTransferStatus;
use App\Models\AuditLog;
use App\Models\Business;
use App\Models\InventoryBatch;
use App\Models\InventoryUnit;
use App\Models\Item;
use App\Models\StockTransfer;
use App\Models\StockTransferLine;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateStockTransfer
{
    /**
     * @param array{
     *     source_warehouse_id: string,
     *     destination_warehouse_id: string,
     *     transfer_date?: ?string,
     *     notes?: ?string,
     *     lines: array<array{
     *         item_id: string,
     *         batch_id?: ?string,
     *         inventory_unit_id?: ?string,
     *         quantity: numeric
     *     }>
     * } $data
     */
    public function execute(User $actor, Business $business, array $data): StockTransfer
    {
        $source = Warehouse::where('id', $data['source_warehouse_id'])->where('business_id', $business->id)->firstOrFail();
        $destination = Warehouse::where('id', $data['destination_warehouse_id'])->where('business_id', $business->id)->firstOrFail();

        if ($source->id === $destination->id) {
            throw ValidationException::withMessages([
                'destination_warehouse_id' => ['Source and destination warehouses cannot be identical.'],
            ]);
        }

        $linesData = (array) ($data['lines'] ?? []);
        if (empty($linesData)) {
            throw ValidationException::withMessages(['lines' => ['Transfer must include at least one item line.']]);
        }

        $transferNumber = 'TRF-'.strtoupper(bin2hex(random_bytes(4)));

        return DB::transaction(function () use ($actor, $business, $source, $destination, $transferNumber, $linesData, $data) {
            $transfer = StockTransfer::create([
                'business_id' => $business->id,
                'transfer_number' => $transferNumber,
                'source_warehouse_id' => $source->id,
                'destination_warehouse_id' => $destination->id,
                'transfer_date' => ! empty($data['transfer_date']) ? Carbon::parse($data['transfer_date']) : now()->toDateString(),
                'status' => StockTransferStatus::Draft,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($linesData as $line) {
                $item = Item::where('id', $line['item_id'])->where('business_id', $business->id)->firstOrFail();
                $qty = (float) $line['quantity'];
                if ($qty <= 0) {
                    throw ValidationException::withMessages(['quantity' => ['Transfer quantity must be greater than zero.']]);
                }

                $batch = ! empty($line['batch_id']) ? InventoryBatch::where('id', $line['batch_id'])->where('business_id', $business->id)->first() : null;
                $unit = ! empty($line['inventory_unit_id']) ? InventoryUnit::where('id', $line['inventory_unit_id'])->where('business_id', $business->id)->first() : null;

                StockTransferLine::create([
                    'stock_transfer_id' => $transfer->id,
                    'item_id' => $item->id,
                    'batch_id' => $batch?->id,
                    'inventory_unit_id' => $unit?->id,
                    'quantity_dispatched' => $qty,
                    'quantity_received' => 0.0000,
                    'quantity_damaged' => 0.0000,
                    'quantity_lost' => 0.0000,
                ]);
            }

            AuditLog::create([
                'business_id' => $business->id,
                'user_id' => $actor->id,
                'action' => 'stock_transfer.created',
                'auditable_type' => StockTransfer::class,
                'auditable_id' => $transfer->id,
                'payload' => [
                    'transfer_number' => $transfer->transfer_number,
                    'source' => $source->name,
                    'destination' => $destination->name,
                    'lines_count' => count($linesData),
                ],
            ]);

            return $transfer->load('lines.item');
        });
    }
}
