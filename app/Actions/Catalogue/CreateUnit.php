<?php

namespace App\Actions\Catalogue;

use App\Enums\CatalogStatus;
use App\Models\AuditLog;
use App\Models\Business;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateUnit
{
    /**
     * @param  array{name: string, code: string, status?: string|CatalogStatus}  $data
     */
    public function execute(User $actor, Business $business, array $data): Unit
    {
        $name = trim($data['name']);
        $code = strtoupper(trim($data['code']));

        if (empty($code)) {
            throw ValidationException::withMessages([
                'code' => ['A valid unit symbol/code is required.'],
            ]);
        }

        if (Unit::where('business_id', $business->id)->where('code', $code)->exists()) {
            throw ValidationException::withMessages([
                'code' => ["A unit with code '{$code}' already exists for this business."],
            ]);
        }

        $status = isset($data['status'])
            ? ($data['status'] instanceof CatalogStatus ? $data['status'] : CatalogStatus::from($data['status']))
            : CatalogStatus::Active;

        return DB::transaction(function () use ($actor, $business, $name, $code, $status) {
            $unit = Unit::create([
                'business_id' => $business->id,
                'name' => $name,
                'code' => $code,
                'status' => $status,
            ]);

            AuditLog::create([
                'business_id' => $business->id,
                'user_id' => $actor->id,
                'action' => 'unit.created',
                'auditable_type' => Unit::class,
                'auditable_id' => $unit->id,
                'payload' => [
                    'name' => $unit->name,
                    'code' => $unit->code,
                    'status' => $unit->status->value,
                ],
            ]);

            return $unit;
        });
    }
}
