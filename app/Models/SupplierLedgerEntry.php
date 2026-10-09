<?php

namespace App\Models;

use App\Enums\SupplierLedgerEntryType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'business_id',
    'supplier_id',
    'entry_type',
    'amount',
    'running_balance',
    'source_type',
    'source_id',
    'source_reference',
    'posted_at',
    'actor_id',
    'notes',
    'reversal_entry_id',
])]
class SupplierLedgerEntry extends Model
{
    use HasFactory, HasUlids;

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'running_balance' => 'decimal:2',
            'posted_at' => 'datetime',
            'entry_type' => SupplierLedgerEntryType::class,
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function getDebitAmountAttribute(): float
    {
        return $this->entry_type->increasesPayable() ? (float) $this->amount : 0.0;
    }

    public function getCreditAmountAttribute(): float
    {
        return ! $this->entry_type->increasesPayable() ? abs((float) $this->amount) : 0.0;
    }
}
