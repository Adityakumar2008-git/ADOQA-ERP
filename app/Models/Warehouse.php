<?php

namespace App\Models;

use App\Enums\WarehouseStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'business_id',
    'name',
    'code',
    'address',
    'is_default',
    'status',
])]
class Warehouse extends Model
{
    use HasFactory, HasUlids;

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'status' => WarehouseStatus::class,
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(StockLocation::class);
    }

    public function balances(): HasMany
    {
        return $this->hasMany(StockBalance::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function isActive(): bool
    {
        return $this->status === WarehouseStatus::Active;
    }
}
