<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'goods_receipt_id',
    'item_id',
    'accepted_quantity',
    'rejected_quantity',
    'actual_unit_cost',
    'batch_number',
    'manufacturing_date',
    'expiry_date',
])]
class GoodsReceiptLine extends Model
{
    use HasFactory, HasUlids;

    protected function casts(): array
    {
        return [
            'accepted_quantity' => 'decimal:4',
            'rejected_quantity' => 'decimal:4',
            'actual_unit_cost' => 'decimal:2',
            'manufacturing_date' => 'date',
            'expiry_date' => 'date',
        ];
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class, 'goods_receipt_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(GoodsReceiptUnit::class);
    }
}
