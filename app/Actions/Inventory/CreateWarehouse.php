<?php

namespace App\Actions\Inventory;

use App\Enums\WarehouseStatus;
use App\Models\AuditLog;
use App\Models\Business;
use App\Models\StockLocation;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateWarehouse
{
    /**
     * @param array{
     *     name: string,
     *     code?: ?string,
     *     address?: ?string,
     *     is_default?: bool,
     *     status?: string|WarehouseStatus
     * } $data
     */
    public function execute(User $actor, Business $business, array $data): Warehouse
    {
        $name = trim($data['name']);
        $code = strtoupper(trim($data['code'] ?? preg_replace('/[^A-Za-z0-9]/', '', substr($name, 0, 6))));

        if (Warehouse::where('business_id', $business->id)->where('code', $code)->exists()) {
            throw ValidationException::withMessages([
                'code' => ["A warehouse with code '{$code}' already exists for this business."],
            ]);
        }

        $isDefault = (bool) ($data['is_default'] ?? false);
        $status = isset($data['status'])
            ? ($data['status'] instanceof WarehouseStatus ? $data['status'] : WarehouseStatus::from($data['status']))
            : WarehouseStatus::Active;

        return DB::transaction(function () use ($actor, $business, $name, $code, $isDefault, $status, $data) {
            if ($isDefault) {
                // Clear any existing default warehouse
                Warehouse::where('business_id', $business->id)->update(['is_default' => false]);
            }

            $warehouse = Warehouse::create([
                'business_id' => $business->id,
                'name' => $name,
                'code' => $code,
                'address' => filled($data['address'] ?? null) ? trim($data['address']) : null,
                'is_default' => $isDefault,
                'status' => $status,
            ]);

            // Create default location for this warehouse
            StockLocation::create([
                'business_id' => $business->id,
                'warehouse_id' => $warehouse->id,
                'name' => 'Main Location',
                'code' => 'DEFAULT',
                'status' => 'active',
            ]);

            AuditLog::create([
                'business_id' => $business->id,
                'user_id' => $actor->id,
                'action' => 'warehouse.created',
                'auditable_type' => Warehouse::class,
                'auditable_id' => $warehouse->id,
                'payload' => [
                    'name' => $warehouse->name,
                    'code' => $warehouse->code,
                    'is_default' => $warehouse->is_default,
                    'status' => $warehouse->status->value,
                ],
            ]);

            return $warehouse;
        });
    }
}
