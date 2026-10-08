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

class UpdateCategory
{
    /**
     * @param  array{name: string, slug?: ?string, description?: ?string, status?: string|CatalogStatus}  $data
     */
    public function execute(User $actor, Business $business, Category $category, array $data): Category
    {
        if ($category->business_id !== $business->id) {
            throw ValidationException::withMessages([
                'category' => ['Cross-tenant operation denied.'],
            ]);
        }

        $name = trim($data['name']);
        $slug = filled($data['slug'] ?? null) ? Str::slug($data['slug']) : Str::slug($name);

        if (empty($slug)) {
            $slug = 'cat-'.Str::random(6);
        }

        if (Category::where('business_id', $business->id)->where('slug', $slug)->where('id', '!=', $category->id)->exists()) {
            throw ValidationException::withMessages([
                'slug' => ["A category with the slug '{$slug}' already exists for this business."],
            ]);
        }

        $oldValues = [
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,
            'status' => $category->status->value,
        ];

        $status = isset($data['status'])
            ? ($data['status'] instanceof CatalogStatus ? $data['status'] : CatalogStatus::from($data['status']))
            : $category->status;

        return DB::transaction(function () use ($actor, $business, $category, $name, $slug, $data, $status, $oldValues) {
            $category->update([
                'name' => $name,
                'slug' => $slug,
                'description' => filled($data['description'] ?? null) ? trim($data['description']) : null,
                'status' => $status,
            ]);

            AuditLog::create([
                'business_id' => $business->id,
                'user_id' => $actor->id,
                'action' => 'category.updated',
                'auditable_type' => Category::class,
                'auditable_id' => $category->id,
                'payload' => [
                    'old' => $oldValues,
                    'new' => [
                        'name' => $category->name,
                        'slug' => $category->slug,
                        'description' => $category->description,
                        'status' => $category->status->value,
                    ],
                ],
            ]);

            return $category;
        });
    }
}
