<?php

namespace Database\Factories;

use App\Enums\MembershipStatus;
use App\Models\Business;
use App\Models\BusinessMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BusinessMember>
 */
class BusinessMemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'user_id' => User::factory(),
            'is_owner' => false,
            'status' => MembershipStatus::Active,
            'joined_at' => now(),
        ];
    }

    /**
     * Indicate that the member is the owner.
     */
    public function owner(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_owner' => true,
        ]);
    }

    /**
     * Indicate that the membership is suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MembershipStatus::Suspended,
        ]);
    }

    /**
     * Indicate that the membership is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MembershipStatus::Inactive,
        ]);
    }
}
