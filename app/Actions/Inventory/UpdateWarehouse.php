<?php

namespace App\Actions\Inventory;

use App\Enums\WarehouseStatus;
use App\Models\AuditLog;
use App\Models\Business;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateWarehouse
{
    /**
     * @param array{
     *     name?: string,
     *     code?: string,
     *     address?: ?string,
     *     is_default?: bool,
     *     status?: string|WarehouseStatus
     * } $data
     */
    public function execute(User $actor, Business $business, Warehouse $warehouse, array $data): Warehouse
    {
        if ($warehouse->business_id !== $business->id) {
            throw ValidationException::withMessages([
                'warehouse' => ['Unauthorized warehouse context.'],
            ]);
        }

        $name = isset($data['name']) ? trim($data['name']) : $warehouse->name;

        if (isset($data['code'])) {
            $code = strtoupper(trim($data['code']));
            if (Warehouse::where('business_id', $business->id)->where('code', $code)->where('id', '!=', $warehouse->id)->exists()) {
                throw ValidationException::withMessages([
                    'code' => ["Another warehouse with code '{$code}' already exists."],
                ]);
            }
        } else {
            $code = $warehouse->code;
        }

        $newStatus = isset($data['status'])
            ? ($data['status'] instanceof WarehouseStatus ? $data['status'] : WarehouseStatus::from($data['status']))
            : $warehouse->status;

        // Invariant: Prevent deactivation if warehouse contains positive stock
        if ($newStatus === WarehouseStatus::Inactive && $warehouse->status === WarehouseStatus::Active) {
            $hasStock = $warehouse->balances()->where('quantity', '>', 0)->exists();
            if ($hasStock) {
                throw ValidationException::withMessages([
                    'status' => ['Cannot deactivate warehouse with active stock balances. Empty or transfer stock before deactivating.'],
                ]);
            }

            if ($warehouse->is_default) {
                throw ValidationException::withMessages([
                    'status' => ['Cannot deactivate the default warehouse. Designate another warehouse as default first.'],
                ]);
            }
        }

        $isDefault = isset($data['is_default']) ? (bool) $data['is_default'] : $warehouse->is_default;

        // Invariant: If removing default flag, there must be another default warehouse
        if (! $isDefault && $warehouse->is_default) {
            $otherDefaultExists = Warehouse::where('business_id', $business->id)
                ->where('id', '!=', $warehouse->id)
                ->where('is_default', true)
                ->exists();

            if (! $otherDefaultExists) {
                throw ValidationException::withMessages([
                    'is_default' => ['The business must have at least one active default warehouse.'],
                ]);
            }
        }

        return DB::transaction(function () use ($actor, $business, $warehouse, $name, $code, $isDefault, $newStatus, $data) {
            if ($isDefault && ! $warehouse->is_default) {
                Warehouse::where('business_id', $business->id)->where('id', '!=', $warehouse->id)->update(['is_default' => false]);
            }

            $warehouse->update([
                'name' => $name,
                'code' => $code,
                'address' => array_key_exists('address', $data) ? (filled($data['address']) ? trim($data['address']) : null) : $warehouse->address,
                'is_default' => $isDefault,
                'status' => $newStatus,
            ]);

            AuditLog::create([
                'business_id' => $business->id,
                'user_id' => $actor->id,
                'action' => 'warehouse.updated',
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
