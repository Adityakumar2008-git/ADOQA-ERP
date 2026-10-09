<?php

namespace App\Actions\Inventory;

use App\Enums\WarehouseStatus;
use App\Models\AuditLog;
use App\Models\Business;
use App\Models\StockLocation;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class InitializeDefaultWarehouse
{
    /**
     * Authoritatively and idempotently initialize the default warehouse for a business.
     */
    public function execute(Business $business, ?User $actor = null): Warehouse
    {
        return DB::transaction(function () use ($business, $actor) {
            $existing = Warehouse::query()
                ->where('business_id', $business->id)
                ->where('is_default', true)
                ->first();

            if ($existing) {
                return $existing;
            }

            $warehouse = Warehouse::create([
                'business_id' => $business->id,
                'name' => 'Main Warehouse',
                'code' => 'MAIN',
                'address' => $business->address_line_1,
                'is_default' => true,
                'status' => WarehouseStatus::Active,
            ]);

            StockLocation::create([
                'business_id' => $business->id,
                'warehouse_id' => $warehouse->id,
                'name' => 'Main Location',
                'code' => 'DEFAULT',
                'status' => 'active',
            ]);

            if ($actor) {
                AuditLog::create([
                    'business_id' => $business->id,
                    'user_id' => $actor->id,
                    'action' => 'warehouse.default_initialized',
                    'auditable_type' => Warehouse::class,
                    'auditable_id' => $warehouse->id,
                    'payload' => [
                        'name' => $warehouse->name,
                        'code' => $warehouse->code,
                        'is_default' => true,
                    ],
                ]);
            }

            return $warehouse;
        });
    }
}
