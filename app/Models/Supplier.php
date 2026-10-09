<?php

namespace App\Models;

use App\Enums\SupplierStatus;
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
    'phone',
    'email',
    'address',
    'gst_number',
    'credit_limit',
    'opening_balance',
    'status',
    'notes',
])]
class Supplier extends Model
{
    use HasFactory, HasUlids;

    protected function casts(): array
    {
        return [
            'credit_limit' => 'decimal:2',
            'opening_balance' => 'decimal:2',
            'status' => SupplierStatus::class,
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function purchaseBills(): HasMany
    {
        return $this->hasMany(PurchaseBill::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(SupplierLedgerEntry::class);
    }

    public function isActive(): bool
    {
        return $this->status === SupplierStatus::Active;
    }

    /**
     * Calculate current total outstanding payable from ledger entries.
     */
    public function currentOutstanding(): float
    {
        return (float) $this->ledgerEntries()->sum('amount');
    }

    public function getCurrentBalanceAttribute(): float
    {
        return $this->currentOutstanding();
    }

    public function getUnallocatedAdvanceAttribute(): float
    {
        return (float) $this->payments()->where('status', 'posted')->sum('advance_amount');
    }
}
