<?php

namespace Database\Factories;

use App\Enums\BusinessStatus;
use App\Models\Business;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Business>
 */
class BusinessFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name' => $name,
            'legal_name' => $name.' Pvt Ltd',
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(100, 999),
            'business_type' => 'retail',
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->numerify('+91##########'),
            'address_line_1' => fake()->streetAddress(),
            'address_line_2' => fake()->secondaryAddress(),
            'city' => fake()->city(),
            'state' => 'Maharashtra',
            'postal_code' => fake()->postcode(),
            'country' => 'IN',
            'currency' => 'INR',
            'timezone' => 'Asia/Kolkata',
            'tax_number' => fake()->numerify('27#####1234#1Z#'),
            'status' => BusinessStatus::Active,
        ];
    }

    /**
     * Indicate that the business is suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BusinessStatus::Suspended,
        ]);
    }

    /**
     * Indicate that the business is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BusinessStatus::Inactive,
        ]);
    }
}
