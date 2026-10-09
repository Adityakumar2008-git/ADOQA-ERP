<?php

namespace App\Actions\Inventory;

use App\Enums\InventoryMode;
use App\Enums\InventoryUnitStatus;
use App\Enums\StockMovementType;
use App\Models\AuditLog;
use App\Models\Business;
use App\Models\InventoryBatch;
use App\Models\InventoryUnit;
use App\Models\Item;
use App\Models\StockLocation;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordOpeningStock
{
    public function __construct(
        protected MutateStock $mutateStock
    ) {}

    /**
     * @param array{
     *     item_id: string,
     *     warehouse_id: string,
     *     location_id?: ?string,
     *     quantity: numeric,
     *     unit_cost?: numeric,
     *     batch_number?: ?string,
     *     manufacturing_date?: ?string,
     *     expiry_date?: ?string,
     *     serial_numbers?: array<string>,
     *     imeis?: array<array{imei1: string, imei2?: ?string}>,
     *     notes?: ?string
     * } $data
     */
    public function execute(User $actor, Business $business, array $data): array
    {
        $item = Item::where('id', $data['item_id'])->where('business_id', $business->id)->firstOrFail();
        $warehouse = Warehouse::where('id', $data['warehouse_id'])->where('business_id', $business->id)->firstOrFail();

        $location = null;
        if (! empty($data['location_id'])) {
            $location = StockLocation::where('id', $data['location_id'])->where('warehouse_id', $warehouse->id)->firstOrFail();
        }

        $quantity = (float) $data['quantity'];
        if ($quantity <= 0) {
            throw ValidationException::withMessages(['quantity' => ['Opening stock quantity must be strictly greater than zero.']]);
        }

        $unitCost = (float) ($data['unit_cost'] ?? $item->cost_price ?? 0.00);
        if ($unitCost < 0) {
            throw ValidationException::withMessages(['unit_cost' => ['Unit cost cannot be negative.']]);
        }

        return DB::transaction(function () use ($actor, $business, $item, $warehouse, $location, $quantity, $unitCost, $data) {
            $results = [];

            if ($item->inventory_mode === InventoryMode::Batch) {
                $batchNumber = strtoupper(trim($data['batch_number'] ?? ''));
                if (empty($batchNumber)) {
                    throw ValidationException::withMessages(['batch_number' => ['Batch number is required for batch items.']]);
                }

                $mfgDate = ! empty($data['manufacturing_date']) ? Carbon::parse($data['manufacturing_date']) : null;
                $expDate = ! empty($data['expiry_date']) ? Carbon::parse($data['expiry_date']) : null;

                if ($mfgDate && $expDate && $expDate->lt($mfgDate)) {
                    throw ValidationException::withMessages(['expiry_date' => ['Expiry date cannot be earlier than manufacturing date.']]);
                }

                $batch = InventoryBatch::firstOrCreate(
                    [
                        'business_id' => $business->id,
                        'item_id' => $item->id,
                        'batch_number' => $batchNumber,
                    ],
                    [
                        'manufacturing_date' => $mfgDate,
                        'expiry_date' => $expDate,
                        'cost_price' => $unitCost,
                        'status' => 'active',
                    ]
                );

                $res = $this->mutateStock->execute(
                    business: $business,
                    item: $item,
                    warehouse: $warehouse,
                    movementType: StockMovementType::OpeningBalance,
                    quantity: $quantity,
                    unitCost: $unitCost,
                    location: $location,
                    batch: $batch,
                    sourceType: 'opening_stock',
                    actor: $actor,
                    notes: $data['notes'] ?? 'Opening Stock Entry'
                );

                $results[] = $res;
            } elseif ($item->inventory_mode === InventoryMode::Serial) {
                $serials = (array) ($data['serial_numbers'] ?? []);
                if (count($serials) !== (int) $quantity) {
                    throw ValidationException::withMessages([
                        'serial_numbers' => ["Exactly {$quantity} unique serial numbers must be provided (received ".count($serials).').'],
                    ]);
                }

                // Check duplicates within supplied list
                $trimmedSerials = array_map('trim', $serials);
                if (count($trimmedSerials) !== count(array_unique($trimmedSerials))) {
                    throw ValidationException::withMessages(['serial_numbers' => ['Duplicate serial numbers found in the supplied list.']]);
                }

                foreach ($trimmedSerials as $serial) {
                    if (InventoryUnit::where('business_id', $business->id)->where('item_id', $item->id)->where('serial_number', $serial)->exists()) {
                        throw ValidationException::withMessages(['serial_numbers' => ["Serial number '{$serial}' is already registered for this item."]]);
                    }

                    $unit = InventoryUnit::create([
                        'business_id' => $business->id,
                        'item_id' => $item->id,
                        'warehouse_id' => $warehouse->id,
                        'location_id' => $location?->id,
                        'serial_number' => $serial,
                        'cost_price' => $unitCost,
                        'status' => InventoryUnitStatus::Available,
                    ]);

                    $res = $this->mutateStock->execute(
                        business: $business,
                        item: $item,
                        warehouse: $warehouse,
                        movementType: StockMovementType::OpeningBalance,
                        quantity: 1.0,
                        unitCost: $unitCost,
                        location: $location,
                        unit: $unit,
                        sourceType: 'opening_stock',
                        actor: $actor,
                        notes: "Opening Stock Serial: {$serial}"
                    );

                    $results[] = $res;
                }
            } elseif ($item->inventory_mode === InventoryMode::Imei) {
                $imeis = (array) ($data['imeis'] ?? []);
                if (count($imeis) !== (int) $quantity) {
                    throw ValidationException::withMessages([
                        'imeis' => ["Exactly {$quantity} IMEI unit definitions must be provided (received ".count($imeis).').'],
                    ]);
                }

                foreach ($imeis as $imeiEntry) {
                    $imei1 = trim($imeiEntry['imei1'] ?? '');
                    $imei2 = filled($imeiEntry['imei2'] ?? null) ? trim($imeiEntry['imei2']) : null;

                    if (empty($imei1)) {
                        throw ValidationException::withMessages(['imeis' => ['IMEI 1 is required for every mobile device unit.']]);
                    }

                    // Platform uniqueness check for IMEIs
                    if (InventoryUnit::where('imei1', $imei1)->orWhere('imei2', $imei1)->exists()) {
                        throw ValidationException::withMessages(['imeis' => ["IMEI '{$imei1}' is already registered in the system."]]);
                    }

                    if ($imei2 !== null && (InventoryUnit::where('imei1', $imei2)->orWhere('imei2', $imei2)->exists())) {
                        throw ValidationException::withMessages(['imeis' => ["Secondary IMEI '{$imei2}' is already registered in the system."]]);
                    }

                    $unit = InventoryUnit::create([
                        'business_id' => $business->id,
                        'item_id' => $item->id,
                        'warehouse_id' => $warehouse->id,
                        'location_id' => $location?->id,
                        'imei1' => $imei1,
                        'imei2' => $imei2,
                        'cost_price' => $unitCost,
                        'status' => InventoryUnitStatus::Available,
                    ]);

                    $res = $this->mutateStock->execute(
                        business: $business,
                        item: $item,
                        warehouse: $warehouse,
                        movementType: StockMovementType::OpeningBalance,
                        quantity: 1.0,
                        unitCost: $unitCost,
                        location: $location,
                        unit: $unit,
                        sourceType: 'opening_stock',
                        actor: $actor,
                        notes: "Opening Stock IMEI: {$imei1}".($imei2 ? " / {$imei2}" : '')
                    );

                    $results[] = $res;
                }
            } else {
                // Normal & Accessory Quantity Items
                $res = $this->mutateStock->execute(
                    business: $business,
                    item: $item,
                    warehouse: $warehouse,
                    movementType: StockMovementType::OpeningBalance,
                    quantity: $quantity,
                    unitCost: $unitCost,
                    location: $location,
                    sourceType: 'opening_stock',
                    actor: $actor,
                    notes: $data['notes'] ?? 'Opening Stock Entry'
                );

                $results[] = $res;
            }

            AuditLog::create([
                'business_id' => $business->id,
                'user_id' => $actor->id,
                'action' => 'inventory.opening_stock_recorded',
                'auditable_type' => Item::class,
                'auditable_id' => $item->id,
                'payload' => [
                    'warehouse_id' => $warehouse->id,
                    'quantity' => $quantity,
                    'inventory_mode' => $item->inventory_mode->value,
                ],
            ]);

            return $results;
        });
    }
}
