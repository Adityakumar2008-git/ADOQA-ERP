<?php

namespace Database\Factories;

use App\Enums\RoleScope;
use App\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Permission>
 */
class PermissionFactory extends Factory
{
    protected $model = Permission::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $action = fake()->unique()->word();

        return [
            'name' => ucfirst($action).' Permission',
            'slug' => 'test.'.Str::slug($action),
            'group' => 'test',
            'scope' => RoleScope::Business,
            'description' => fake()->sentence(),
        ];
    }

    /**
     * Indicate that the permission is platform-scoped.
     */
    public function platform(): static
    {
        return $this->state(fn () => [
            'scope' => RoleScope::Platform,
            'group' => 'platform',
        ]);
    }
}
