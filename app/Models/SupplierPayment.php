<?php

namespace App\Models;

use App\Enums\SupplierPaymentMode;
use App\Enums\SupplierPaymentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'business_id',
    'supplier_id',
    'payment_number',
    'payment_date',
    'amount',
    'payment_mode',
    'reference_number',
    'allocated_amount',
    'advance_amount',
    'status',
    'notes',
    'posted_by',
    'posted_at',
    'reversed_by',
    'reversed_at',
    'idempotency_key',
])]
class SupplierPayment extends Model
{
    use HasFactory, HasUlids;

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'posted_at' => 'datetime',
            'reversed_at' => 'datetime',
            'amount' => 'decimal:2',
            'allocated_amount' => 'decimal:2',
            'advance_amount' => 'decimal:2',
            'status' => SupplierPaymentStatus::class,
            'payment_mode' => SupplierPaymentMode::class,
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

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function reverser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(SupplierPaymentAllocation::class);
    }

    public function unallocatedAmount(): float
    {
        return max(0.0, (float) $this->amount - (float) $this->allocated_amount);
    }
}
