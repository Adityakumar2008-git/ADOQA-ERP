<?php

namespace App\Actions\Catalogue;

use App\Enums\CatalogStatus;
use App\Models\AuditLog;
use App\Models\Brand;
use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ActivateBrand
{
    public function execute(User $actor, Business $business, Brand $brand): Brand
    {
        if ($brand->business_id !== $business->id) {
            throw ValidationException::withMessages([
                'brand' => ['Cross-tenant operation denied.'],
            ]);
        }

        if ($brand->status === CatalogStatus::Active) {
            return $brand;
        }

        return DB::transaction(function () use ($actor, $business, $brand) {
            $brand->update(['status' => CatalogStatus::Active]);

            AuditLog::create([
                'business_id' => $business->id,
                'user_id' => $actor->id,
                'action' => 'brand.activated',
                'auditable_type' => Brand::class,
                'auditable_id' => $brand->id,
                'payload' => [
                    'name' => $brand->name,
                    'slug' => $brand->slug,
                ],
            ]);

            return $brand;
        });
    }
}
