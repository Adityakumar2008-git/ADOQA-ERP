<?php

namespace App\Models;

use App\Enums\InventoryUnitStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'business_id',
    'item_id',
    'warehouse_id',
    'location_id',
    'serial_number',
    'imei1',
    'imei2',
    'cost_price',
    'status',
])]
class InventoryUnit extends Model
{
    use HasFactory, HasUlids;

    protected function casts(): array
    {
        return [
            'status' => InventoryUnitStatus::class,
            'cost_price' => 'decimal:2',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class);
    }

    public function isAvailable(): bool
    {
        return $this->status === InventoryUnitStatus::Available;
    }
}
