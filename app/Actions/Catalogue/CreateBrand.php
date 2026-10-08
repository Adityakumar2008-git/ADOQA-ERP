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

class CreateBrand
{
    /**
     * @param  array{name: string, slug?: ?string, description?: ?string, status?: string|CatalogStatus}  $data
     */
    public function execute(User $actor, Business $business, array $data): Brand
    {
        $name = trim($data['name']);
        $slug = filled($data['slug'] ?? null) ? Str::slug($data['slug']) : Str::slug($name);

        if (empty($slug)) {
            $slug = 'brand-'.Str::random(6);
        }

        if (Brand::where('business_id', $business->id)->where('slug', $slug)->exists()) {
            throw ValidationException::withMessages([
                'slug' => ["A brand with the slug '{$slug}' already exists for this business."],
            ]);
        }

        $status = isset($data['status'])
            ? ($data['status'] instanceof CatalogStatus ? $data['status'] : CatalogStatus::from($data['status']))
            : CatalogStatus::Active;

        return DB::transaction(function () use ($actor, $business, $name, $slug, $data, $status) {
            $brand = Brand::create([
                'business_id' => $business->id,
                'name' => $name,
                'slug' => $slug,
                'description' => filled($data['description'] ?? null) ? trim($data['description']) : null,
                'status' => $status,
            ]);

            AuditLog::create([
                'business_id' => $business->id,
                'user_id' => $actor->id,
                'action' => 'brand.created',
                'auditable_type' => Brand::class,
                'auditable_id' => $brand->id,
                'payload' => [
                    'name' => $brand->name,
                    'slug' => $brand->slug,
                    'status' => $brand->status->value,
                ],
            ]);

            return $brand;
        });
    }
}
