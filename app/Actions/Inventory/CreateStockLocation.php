<?php

namespace App\Actions\Inventory;

use App\Models\AuditLog;
use App\Models\Business;
use App\Models\StockLocation;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Validation\ValidationException;

class CreateStockLocation
{
    /**
     * @param array{
     *     name: string,
     *     code?: ?string
     * } $data
     */
    public function execute(User $actor, Business $business, Warehouse $warehouse, array $data): StockLocation
    {
        if ($warehouse->business_id !== $business->id) {
            throw ValidationException::withMessages([
                'warehouse' => ['Warehouse does not belong to this business.'],
            ]);
        }

        $name = trim($data['name']);
        $code = filled($data['code'] ?? null) ? strtoupper(trim($data['code'])) : null;

        if (StockLocation::where('warehouse_id', $warehouse->id)->where('name', $name)->exists()) {
            throw ValidationException::withMessages([
                'name' => ["A location named '{$name}' already exists in this warehouse."],
            ]);
        }

        $location = StockLocation::create([
            'business_id' => $business->id,
            'warehouse_id' => $warehouse->id,
            'name' => $name,
            'code' => $code,
            'status' => 'active',
        ]);

        AuditLog::create([
            'business_id' => $business->id,
            'user_id' => $actor->id,
            'action' => 'stock_location.created',
            'auditable_type' => StockLocation::class,
            'auditable_id' => $location->id,
            'payload' => [
                'warehouse_id' => $warehouse->id,
                'name' => $location->name,
                'code' => $location->code,
            ],
        ]);

        return $location;
    }
}
