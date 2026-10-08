<?php

namespace Database\Factories;

use App\Enums\InventoryMode;
use App\Enums\ItemStatus;
use App\Models\Business;
use App\Models\Item;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Item>
 */
class ItemFactory extends Factory
{
    protected $model = Item::class;

    public function definition(): array
    {
        $cost = fake()->randomFloat(2, 50, 500);
        $sale = $cost + fake()->randomFloat(2, 10, 200);
        $mrp = $sale + fake()->randomFloat(2, 5, 50);

        return [
            'business_id' => Business::factory(),
            'name' => ucfirst(fake()->words(3, true)),
            'description' => fake()->sentence(),
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####-??')),
            'barcode' => fake()->unique()->numerify('890##########'),
            'category_id' => null,
            'brand_id' => null,
            'unit_id' => Unit::factory(),
            'inventory_mode' => InventoryMode::Normal,
            'cost_price' => $cost,
            'sale_price' => $sale,
            'mrp' => $mrp,
            'tax_rate' => 18.00,
            'minimum_stock_threshold' => 10.00,
            'status' => ItemStatus::Active,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ItemStatus::Inactive,
        ]);
    }
}
