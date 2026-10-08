<?php

namespace App\Actions\Catalogue;

use App\Models\AuditLog;
use App\Models\Brand;
use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteBrand
{
    public function execute(User $actor, Business $business, Brand $brand): void
    {
        if ($brand->business_id !== $business->id) {
            throw ValidationException::withMessages([
                'brand' => ['Cross-tenant operation denied.'],
            ]);
        }

        if ($brand->items()->exists()) {
            throw ValidationException::withMessages([
                'brand' => ['Cannot delete brand referenced by existing items. Please deactivate it instead.'],
            ]);
        }

        DB::transaction(function () use ($actor, $business, $brand) {
            $payload = [
                'name' => $brand->name,
                'slug' => $brand->slug,
            ];

            $brand->delete();

            AuditLog::create([
                'business_id' => $business->id,
                'user_id' => $actor->id,
                'action' => 'brand.deleted',
                'auditable_type' => Brand::class,
                'auditable_id' => $brand->id,
                'payload' => $payload,
            ]);
        });
    }
}
