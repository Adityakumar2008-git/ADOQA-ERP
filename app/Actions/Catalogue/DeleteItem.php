<?php

namespace App\Actions\Catalogue;

use App\Models\AuditLog;
use App\Models\Business;
use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteItem
{
    public function execute(User $actor, Business $business, Item $item): void
    {
        if ($item->business_id !== $business->id) {
            throw ValidationException::withMessages([
                'item' => ['Cross-tenant operation denied.'],
            ]);
        }

        DB::transaction(function () use ($actor, $business, $item) {
            $payload = [
                'name' => $item->name,
                'sku' => $item->sku,
                'barcode' => $item->barcode,
            ];

            $item->delete();

            AuditLog::create([
                'business_id' => $business->id,
                'user_id' => $actor->id,
                'action' => 'item.deleted',
                'auditable_type' => Item::class,
                'auditable_id' => $item->id,
                'payload' => $payload,
            ]);
        });
    }
}
