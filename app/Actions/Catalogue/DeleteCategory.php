<?php

namespace App\Actions\Catalogue;

use App\Models\AuditLog;
use App\Models\Business;
use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteCategory
{
    public function execute(User $actor, Business $business, Category $category): void
    {
        if ($category->business_id !== $business->id) {
            throw ValidationException::withMessages([
                'category' => ['Cross-tenant operation denied.'],
            ]);
        }

        if ($category->items()->exists()) {
            throw ValidationException::withMessages([
                'category' => ['Cannot delete category referenced by existing items. Please deactivate it instead.'],
            ]);
        }

        DB::transaction(function () use ($actor, $business, $category) {
            $payload = [
                'name' => $category->name,
                'slug' => $category->slug,
            ];

            $category->delete();

            AuditLog::create([
                'business_id' => $business->id,
                'user_id' => $actor->id,
                'action' => 'category.deleted',
                'auditable_type' => Category::class,
                'auditable_id' => $category->id,
                'payload' => $payload,
            ]);
        });
    }
}
