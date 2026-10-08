<?php

namespace Database\Factories;

use App\Enums\CatalogStatus;
use App\Models\Business;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Unit>
 */
class UnitFactory extends Factory
{
    protected $model = Unit::class;

    public function definition(): array
    {
        $code = strtoupper(fake()->unique()->lexify('???'));

        return [
            'business_id' => Business::factory(),
            'name' => ucfirst(fake()->word()),
            'code' => $code,
            'status' => CatalogStatus::Active,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CatalogStatus::Inactive,
        ]);
    }
}
