<?php

namespace App\Models;

use App\Enums\BusinessModule;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'business_id',
    'module_slug',
    'is_enabled',
    'updated_by',
])]
class BusinessModuleSetting extends Model
{
    use HasFactory, HasUlids;

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'module_slug' => BusinessModule::class,
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
