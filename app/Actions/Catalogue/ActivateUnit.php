<?php

namespace App\Actions\Catalogue;

use App\Enums\CatalogStatus;
use App\Models\AuditLog;
use App\Models\Business;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ActivateUnit
{
    public function execute(User $actor, Business $business, Unit $unit): Unit
    {
        if ($unit->business_id !== $business->id) {
            throw ValidationException::withMessages([
                'unit' => ['Cross-tenant operation denied.'],
            ]);
        }

        if ($unit->status === CatalogStatus::Active) {
            return $unit;
        }

        return DB::transaction(function () use ($actor, $business, $unit) {
            $unit->update(['status' => CatalogStatus::Active]);

            AuditLog::create([
                'business_id' => $business->id,
                'user_id' => $actor->id,
                'action' => 'unit.activated',
                'auditable_type' => Unit::class,
                'auditable_id' => $unit->id,
                'payload' => [
                    'name' => $unit->name,
                    'code' => $unit->code,
                ],
            ]);

            return $unit;
        });
    }
}
