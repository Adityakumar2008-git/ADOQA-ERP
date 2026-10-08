<?php

namespace App\Models;

use App\Enums\InventoryMode;
use App\Enums\ItemStatus;
use Database\Factories\ItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'business_id',
    'name',
    'description',
    'sku',
    'barcode',
    'category_id',
    'brand_id',
    'unit_id',
    'inventory_mode',
    'cost_price',
    'sale_price',
    'mrp',
    'tax_rate',
    'minimum_stock_threshold',
    'status',
])]
class Item extends Model
{
    /** @use HasFactory<ItemFactory> */
    use HasFactory, HasUlids;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'inventory_mode' => InventoryMode::class,
            'status' => ItemStatus::class,
            'cost_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'mrp' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'minimum_stock_threshold' => 'decimal:2',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function isActive(): bool
    {
        return $this->status === ItemStatus::Active;
    }
}
