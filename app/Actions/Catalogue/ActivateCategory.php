<?php

namespace App\Actions\Catalogue;

use App\Enums\CatalogStatus;
use App\Models\AuditLog;
use App\Models\Business;
use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ActivateCategory
{
    public function execute(User $actor, Business $business, Category $category): Category
    {
        if ($category->business_id !== $business->id) {
            throw ValidationException::withMessages([
                'category' => ['Cross-tenant operation denied.'],
            ]);
        }

        if ($category->status === CatalogStatus::Active) {
            return $category;
        }

        return DB::transaction(function () use ($actor, $business, $category) {
            $category->update(['status' => CatalogStatus::Active]);

            AuditLog::create([
                'business_id' => $business->id,
                'user_id' => $actor->id,
                'action' => 'category.activated',
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
