<?php

namespace App\Actions\Catalogue;

use App\Enums\CatalogStatus;
use App\Models\AuditLog;
use App\Models\Business;
use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateCategory
{
    /**
     * @param  array{name: string, slug?: ?string, description?: ?string, status?: string|CatalogStatus}  $data
     */
    public function execute(User $actor, Business $business, array $data): Category
    {
        $name = trim($data['name']);
        $slug = filled($data['slug'] ?? null) ? Str::slug($data['slug']) : Str::slug($name);

        if (empty($slug)) {
            $slug = 'cat-'.Str::random(6);
        }

        // Check business-scoped unique slug
        if (Category::where('business_id', $business->id)->where('slug', $slug)->exists()) {
            throw ValidationException::withMessages([
                'slug' => ["A category with the slug '{$slug}' already exists for this business."],
            ]);
        }

        $status = isset($data['status'])
            ? ($data['status'] instanceof CatalogStatus ? $data['status'] : CatalogStatus::from($data['status']))
            : CatalogStatus::Active;

        return DB::transaction(function () use ($actor, $business, $name, $slug, $data, $status) {
            $category = Category::create([
                'business_id' => $business->id,
                'name' => $name,
                'slug' => $slug,
                'description' => filled($data['description'] ?? null) ? trim($data['description']) : null,
                'status' => $status,
            ]);

            AuditLog::create([
                'business_id' => $business->id,
                'user_id' => $actor->id,
                'action' => 'category.created',
                'auditable_type' => Category::class,
                'auditable_id' => $category->id,
                'payload' => [
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'status' => $category->status->value,
                ],
            ]);

            return $category;
        });
    }
}
