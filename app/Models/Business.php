<?php

namespace App\Models;

use App\Enums\BusinessStatus;
use App\Enums\SubscriptionStatus;
use Database\Factories\BusinessFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'name',
    'legal_name',
    'slug',
    'business_type',
    'email',
    'phone',
    'address_line_1',
    'address_line_2',
    'city',
    'state',
    'postal_code',
    'country',
    'currency',
    'timezone',
    'tax_number',
    'status',
    'allow_negative_stock',
])]
class Business extends Model
{
    /** @use HasFactory<BusinessFactory> */
    use HasFactory, HasUlids;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => BusinessStatus::class,
            'allow_negative_stock' => 'boolean',
        ];
    }

    /**
     * Members belonging to this business.
     */
    public function members(): HasMany
    {
        return $this->hasMany(BusinessMember::class);
    }

    /**
     * Users associated with this business through memberships.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'business_members')
            ->withPivot(['is_owner', 'status', 'joined_at'])
            ->withTimestamps();
    }

    /**
     * The primary owner membership of this business.
     */
    public function ownerMembership(): HasOne
    {
        return $this->hasOne(BusinessMember::class)->where('is_owner', true);
    }

    /**
     * Retrieve the primary owner user of this business.
     */
    public function getOwnerAttribute(): ?User
    {
        return $this->ownerMembership?->user;
    }

    /**
     * Check if the business is active and permitted to operate.
     */
    public function isActive(): bool
    {
        return $this->status === BusinessStatus::Active;
    }

    /**
     * Custom roles defined specifically by this business.
     */
    public function customRoles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    /**
     * User role assignments in this business context.
     */
    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class);
    }

    /**
     * Subscriptions belonging to this business.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * The current usable/active subscription for this business.
     */
    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->whereIn('status', [
                SubscriptionStatus::Trial,
                SubscriptionStatus::Active,
                SubscriptionStatus::PastDue,
            ])
            ->latest('starts_at');
    }

    /**
     * Check if the business is currently suspended.
     */
    public function isSuspended(): bool
    {
        return $this->status === BusinessStatus::Suspended;
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function brands(): HasMany
    {
        return $this->hasMany(Brand::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class);
    }

    public function defaultWarehouse(): HasOne
    {
        return $this->hasOne(Warehouse::class)->where('is_default', true);
    }

    public function stockBalances(): HasMany
    {
        return $this->hasMany(StockBalance::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function purchaseBills(): HasMany
    {
        return $this->hasMany(PurchaseBill::class);
    }

    public function goodsReceipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class);
    }

    public function supplierPayments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class);
    }

    public function supplierLedgerEntries(): HasMany
    {
        return $this->hasMany(SupplierLedgerEntry::class);
    }

    public function moduleSettings(): HasMany
    {
        return $this->hasMany(BusinessModuleSetting::class);
    }

    public function rolePermissionOverrides(): HasMany
    {
        return $this->hasMany(BusinessRolePermissionOverride::class);
    }
}
