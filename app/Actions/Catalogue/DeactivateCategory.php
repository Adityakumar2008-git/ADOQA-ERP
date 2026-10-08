<?php

namespace App\Actions\Catalogue;

use App\Enums\CatalogStatus;
use App\Models\AuditLog;
use App\Models\Business;
use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeactivateCategory
{
    public function execute(User $actor, Business $business, Category $category): Category
    {
        if ($category->business_id !== $business->id) {
            throw ValidationException::withMessages([
                'category' => ['Cross-tenant operation denied.'],
            ]);
        }

        if ($category->status === CatalogStatus::Inactive) {
            return $category;
        }

        return DB::transaction(function () use ($actor, $business, $category) {
            $category->update(['status' => CatalogStatus::Inactive]);

            AuditLog::create([
                'business_id' => $business->id,
                'user_id' => $actor->id,
                'action' => 'category.deactivated',
                'auditable_type' => Category::class,
                'auditable_id' => $category->id,
                'payload' => [
                    'name' => $category->name,
                    'slug' => $category->slug,
                ],
            ]);

            return $category;
        });
    }
}
