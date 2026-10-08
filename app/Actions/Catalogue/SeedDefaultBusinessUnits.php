<?php

namespace App\Actions\Catalogue;

use App\Enums\CatalogStatus;
use App\Models\Business;
use App\Models\Unit;

class SeedDefaultBusinessUnits
{
    /**
     * Canonical units required across retail, electronics, pharmacy, and trade businesses.
     *
     * @var array<int, array{name: string, code: string}>
     */
    public const CANONICAL_UNITS = [
        ['name' => 'Piece', 'code' => 'PCS'],
        ['name' => 'Bag', 'code' => 'BAG'],
        ['name' => 'Kilogram', 'code' => 'KG'],
        ['name' => 'Meter', 'code' => 'MTR'],
        ['name' => 'Liter', 'code' => 'LTR'],
        ['name' => 'Box', 'code' => 'BOX'],
        ['name' => 'Set', 'code' => 'SET'],
        ['name' => 'Tin', 'code' => 'TIN'],
        ['name' => 'Roll', 'code' => 'ROL'],
        ['name' => 'Dozen', 'code' => 'DOZ'],
    ];

    /**
     * Seed canonical default units for a business idempotently.
     */
    public function execute(Business $business): void
    {
        foreach (self::CANONICAL_UNITS as $unitDef) {
            Unit::firstOrCreate(
                [
                    'business_id' => $business->id,
                    'code' => $unitDef['code'],
                ],
                [
                    'name' => $unitDef['name'],
                    'status' => CatalogStatus::Active,
                ]
            );
        }
    }
}
