<?php

namespace App\Actions\Catalogue;

use App\Models\AuditLog;
use App\Models\Business;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteUnit
{
    public function execute(User $actor, Business $business, Unit $unit): void
    {
        if ($unit->business_id !== $business->id) {
            throw ValidationException::withMessages([
                'unit' => ['Cross-tenant operation denied.'],
            ]);
        }

        if ($unit->items()->exists()) {
            throw ValidationException::withMessages([
                'unit' => ['Cannot delete unit referenced by existing items. Please deactivate it instead.'],
            ]);
        }

        DB::transaction(function () use ($actor, $business, $unit) {
            $payload = [
                'name' => $unit->name,
                'code' => $unit->code,
            ];

            $unit->delete();

            AuditLog::create([
                'business_id' => $business->id,
                'user_id' => $actor->id,
                'action' => 'unit.deleted',
                'auditable_type' => Unit::class,
                'auditable_id' => $unit->id,
                'payload' => $payload,
            ]);
        });
    }
}
