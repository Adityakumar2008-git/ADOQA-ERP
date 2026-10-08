<?php

namespace App\Actions\Catalogue;

use App\Enums\ItemStatus;
use App\Models\AuditLog;
use App\Models\Business;
use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ActivateItem
{
    public function execute(User $actor, Business $business, Item $item): Item
    {
        if ($item->business_id !== $business->id) {
            throw ValidationException::withMessages([
                'item' => ['Cross-tenant operation denied.'],
            ]);
        }

        if ($item->status === ItemStatus::Active) {
            return $item;
        }

        return DB::transaction(function () use ($actor, $business, $item) {
            $item->update(['status' => ItemStatus::Active]);

            AuditLog::create([
                'business_id' => $business->id,
                'user_id' => $actor->id,
                'action' => 'item.activated',
                'auditable_type' => Item::class,
                'auditable_id' => $item->id,
                'payload' => [
                    'name' => $item->name,
                    'sku' => $item->sku,
                ],
            ]);

            return $item;
        });
    }
}
