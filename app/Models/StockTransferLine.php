<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'stock_transfer_id',
    'item_id',
    'batch_id',
    'inventory_unit_id',
    'quantity_dispatched',
    'quantity_received',
    'quantity_damaged',
    'quantity_lost',
])]
class StockTransferLine extends Model
{
    use HasFactory, HasUlids;

    protected function casts(): array
    {
        return [
            'quantity_dispatched' => 'decimal:4',
            'quantity_received' => 'decimal:4',
            'quantity_damaged' => 'decimal:4',
            'quantity_lost' => 'decimal:4',
        ];
    }

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class, 'stock_transfer_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class, 'batch_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(InventoryUnit::class, 'inventory_unit_id');
    }
}
