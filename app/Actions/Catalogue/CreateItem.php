<?php

namespace App\Actions\Catalogue;

use App\Enums\InventoryMode;
use App\Enums\ItemStatus;
use App\Models\AuditLog;
use App\Models\Brand;
use App\Models\Business;
use App\Models\Category;
use App\Models\Item;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateItem
{
    /**
     * @param array{
     *     name: string,
     *     description?: ?string,
     *     sku?: ?string,
     *     barcode?: ?string,
     *     category_id?: ?string,
     *     brand_id?: ?string,
     *     unit_id: string,
     *     inventory_mode?: string|InventoryMode,
     *     cost_price: numeric,
     *     sale_price: numeric,
     *     mrp?: ?numeric,
     *     tax_rate?: ?numeric,
     *     minimum_stock_threshold?: numeric,
     *     status?: string|ItemStatus
     * } $data
     */
    public function execute(User $actor, Business $business, array $data): Item
    {
        $name = trim($data['name']);

        // Normalize SKU: empty string becomes null, trimmed uppercase
        $sku = filled($data['sku'] ?? null) ? strtoupper(trim($data['sku'])) : null;
        if ($sku !== null && Item::where('business_id', $business->id)->where('sku', $sku)->exists()) {
            throw ValidationException::withMessages([
                'sku' => ["An item with SKU '{$sku}' already exists for this business."],
            ]);
        }

        // Normalize Barcode: empty string becomes null, trimmed
        $barcode = filled($data['barcode'] ?? null) ? trim($data['barcode']) : null;
        if ($barcode !== null && Item::where('business_id', $business->id)->where('barcode', $barcode)->exists()) {
            throw ValidationException::withMessages([
                'barcode' => ["An item with Barcode '{$barcode}' already exists for this business."],
            ]);
        }

        // Category validation
        $categoryId = $data['category_id'] ?? null;
        if ($categoryId) {
            $category = Category::where('id', $categoryId)->where('business_id', $business->id)->first();
            if (! $category) {
                throw ValidationException::withMessages([
                    'category_id' => ['The selected category does not exist in your business.'],
                ]);
            }
            if (! $category->isActive()) {
                throw ValidationException::withMessages([
                    'category_id' => ['Cannot assign an inactive category to an item.'],
                ]);
            }
        }

        // Brand validation
        $brandId = $data['brand_id'] ?? null;
        if ($brandId) {
            $brand = Brand::where('id', $brandId)->where('business_id', $business->id)->first();
            if (! $brand) {
                throw ValidationException::withMessages([
                    'brand_id' => ['The selected brand does not exist in your business.'],
                ]);
            }
            if (! $brand->isActive()) {
                throw ValidationException::withMessages([
                    'brand_id' => ['Cannot assign an inactive brand to an item.'],
                ]);
            }
        }

        // Unit validation (Mandatory)
        $unitId = $data['unit_id'] ?? null;
        $unit = Unit::where('id', $unitId)->where('business_id', $business->id)->first();
        if (! $unit) {
            throw ValidationException::withMessages([
                'unit_id' => ['A valid unit belonging to your business is required.'],
            ]);
        }
        if (! $unit->isActive()) {
            throw ValidationException::withMessages([
                'unit_id' => ['Cannot assign an inactive unit to an item.'],
            ]);
        }

        // Mode validation
        $inventoryMode = isset($data['inventory_mode'])
            ? ($data['inventory_mode'] instanceof InventoryMode ? $data['inventory_mode'] : InventoryMode::from($data['inventory_mode']))
            : InventoryMode::Normal;

        // Pricing boundary validation
        $costPrice = (float) ($data['cost_price'] ?? 0);
        $salePrice = (float) ($data['sale_price'] ?? 0);
        $mrp = filled($data['mrp'] ?? null) ? (float) $data['mrp'] : null;

        if ($costPrice < 0) {
            throw ValidationException::withMessages(['cost_price' => ['Cost price cannot be negative.']]);
        }
        if ($salePrice < 0) {
            throw ValidationException::withMessages(['sale_price' => ['Sale price cannot be negative.']]);
        }
        if ($mrp !== null) {
            if ($mrp < 0) {
                throw ValidationException::withMessages(['mrp' => ['MRP cannot be negative.']]);
            }
            if ($mrp < $salePrice) {
                throw ValidationException::withMessages(['mrp' => ['MRP cannot be lower than the sale price.']]);
            }
        }

        // Tax rate validation
        $taxRate = filled($data['tax_rate'] ?? null) ? (float) $data['tax_rate'] : null;
        if ($taxRate !== null && ($taxRate < 0 || $taxRate > 100)) {
            throw ValidationException::withMessages(['tax_rate' => ['Tax rate must be between 0% and 100%.']]);
        }

        // Minimum stock threshold
        $threshold = (float) ($data['minimum_stock_threshold'] ?? 0);
        if ($threshold < 0) {
            throw ValidationException::withMessages(['minimum_stock_threshold' => ['Threshold cannot be negative.']]);
        }

        // Status
        $status = isset($data['status'])
            ? ($data['status'] instanceof ItemStatus ? $data['status'] : ItemStatus::from($data['status']))
            : ItemStatus::Active;

        return DB::transaction(function () use (
            $actor, $business, $name, $sku, $barcode, $categoryId, $brandId,
            $unitId, $inventoryMode, $costPrice, $salePrice, $mrp, $taxRate,
            $threshold, $status, $data
        ) {
            $item = Item::create([
                'business_id' => $business->id,
                'name' => $name,
                'description' => filled($data['description'] ?? null) ? trim($data['description']) : null,
                'sku' => $sku,
                'barcode' => $barcode,
                'category_id' => $categoryId,
                'brand_id' => $brandId,
                'unit_id' => $unitId,
                'inventory_mode' => $inventoryMode,
                'cost_price' => $costPrice,
                'sale_price' => $salePrice,
                'mrp' => $mrp,
                'tax_rate' => $taxRate,
                'minimum_stock_threshold' => $threshold,
                'status' => $status,
            ]);

            AuditLog::create([
                'business_id' => $business->id,
                'user_id' => $actor->id,
                'action' => 'item.created',
                'auditable_type' => Item::class,
                'auditable_id' => $item->id,
                'payload' => [
                    'name' => $item->name,
                    'sku' => $item->sku,
                    'barcode' => $item->barcode,
                    'inventory_mode' => $item->inventory_mode->value,
                    'sale_price' => (string) $item->sale_price,
                    'status' => $item->status->value,
                ],
            ]);

            return $item;
        });
    }
}
