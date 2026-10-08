<?php

namespace App\Actions\Catalogue;

use App\Enums\CatalogStatus;
use App\Models\AuditLog;
use App\Models\Business;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateUnit
{
    /**
     * @param  array{name: string, code: string, status?: string|CatalogStatus}  $data
     */
    public function execute(User $actor, Business $business, Unit $unit, array $data): Unit
    {
        if ($unit->business_id !== $business->id) {
            throw ValidationException::withMessages([
                'unit' => ['Cross-tenant operation denied.'],
            ]);
        }

        $name = trim($data['name']);
        $code = strtoupper(trim($data['code']));

        if (empty($code)) {
            throw ValidationException::withMessages([
                'code' => ['A valid unit symbol/code is required.'],
            ]);
        }

        if (Unit::where('business_id', $business->id)->where('code', $code)->where('id', '!=', $unit->id)->exists()) {
            throw ValidationException::withMessages([
                'code' => ["A unit with code '{$code}' already exists for this business."],
            ]);
        }

        $oldValues = [
            'name' => $unit->name,
            'code' => $unit->code,
            'status' => $unit->status->value,
        ];

        $status = isset($data['status'])
            ? ($data['status'] instanceof CatalogStatus ? $data['status'] : CatalogStatus::from($data['status']))
            : $unit->status;

        return DB::transaction(function () use ($actor, $business, $unit, $name, $code, $status, $oldValues) {
            $unit->update([
                'name' => $name,
                'code' => $code,
                'status' => $status,
            ]);

            AuditLog::create([
                'business_id' => $business->id,
                'user_id' => $actor->id,
                'action' => 'unit.updated',
                'auditable_type' => Unit::class,
                'auditable_id' => $unit->id,
                'payload' => [
                    'old' => $oldValues,
                    'new' => [
                        'name' => $unit->name,
                        'code' => $unit->code,
                        'status' => $unit->status->value,
                    ],
                ],
            ]);

            return $unit;
        });
    }
}
