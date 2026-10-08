<?php

namespace Database\Factories;

use App\Enums\RoleScope;
use App\Models\Business;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    protected $model = Role::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->jobTitle();

        return [
            'business_id' => Business::factory(),
            'name' => $name,
            'slug' => Str::slug($name.'-'.Str::random(5)),
            'scope' => RoleScope::Business,
            'description' => fake()->sentence(),
            'is_system' => false,
        ];
    }

    /**
     * Indicate that the role is platform-scoped.
     */
    public function platform(): static
    {
        return $this->state(fn () => [
            'business_id' => null,
            'scope' => RoleScope::Platform,
        ]);
    }

    /**
     * Indicate that the role is system-defined.
     */
    public function system(): static
    {
        return $this->state(fn () => [
            'is_system' => true,
        ]);
    }
}
