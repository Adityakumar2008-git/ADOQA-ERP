<?php

namespace App\Actions\Catalogue;

use App\Enums\CatalogStatus;
use App\Models\AuditLog;
use App\Models\Brand;
use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UpdateBrand
{
    /**
     * @param  array{name: string, slug?: ?string, description?: ?string, status?: string|CatalogStatus}  $data
     */
    public function execute(User $actor, Business $business, Brand $brand, array $data): Brand
    {
        if ($brand->business_id !== $business->id) {
            throw ValidationException::withMessages([
                'brand' => ['Cross-tenant operation denied.'],
            ]);
        }

        $name = trim($data['name']);
        $slug = filled($data['slug'] ?? null) ? Str::slug($data['slug']) : Str::slug($name);

        if (empty($slug)) {
            $slug = 'brand-'.Str::random(6);
        }

        if (Brand::where('business_id', $business->id)->where('slug', $slug)->where('id', '!=', $brand->id)->exists()) {
            throw ValidationException::withMessages([
                'slug' => ["A brand with the slug '{$slug}' already exists for this business."],
            ]);
        }

        $oldValues = [
            'name' => $brand->name,
            'slug' => $brand->slug,
            'description' => $brand->description,
            'status' => $brand->status->value,
        ];

        $status = isset($data['status'])
            ? ($data['status'] instanceof CatalogStatus ? $data['status'] : CatalogStatus::from($data['status']))
            : $brand->status;

        return DB::transaction(function () use ($actor, $business, $brand, $name, $slug, $data, $status, $oldValues) {
            $brand->update([
                'name' => $name,
                'slug' => $slug,
                'description' => filled($data['description'] ?? null) ? trim($data['description']) : null,
                'status' => $status,
            ]);

            AuditLog::create([
                'business_id' => $business->id,
                'user_id' => $actor->id,
                'action' => 'brand.updated',
                'auditable_type' => Brand::class,
                'auditable_id' => $brand->id,
                'payload' => [
                    'old' => $oldValues,
                    'new' => [
                        'name' => $brand->name,
                        'slug' => $brand->slug,
                        'description' => $brand->description,
                        'status' => $brand->status->value,
                    ],
                ],
            ]);

            return $brand;
        });
    }
}
