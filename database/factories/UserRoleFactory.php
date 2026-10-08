<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserRole>
 */
class UserRoleFactory extends Factory
{
    protected $model = UserRole::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $business = Business::factory()->create();
        $user = User::factory()->create();

        // Assign membership to user so invariants pass
        $user->membership()->create([
            'business_id' => $business->id,
            'is_owner' => false,
            'status' => 'active',
        ]);

        $role = Role::factory()->create([
            'business_id' => $business->id,
        ]);

        return [
            'user_id' => $user->id,
            'role_id' => $role->id,
            'business_id' => $business->id,
        ];
    }
}
