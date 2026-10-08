<?php

namespace Database\Factories;

use App\Enums\BillingCycle;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SubscriptionPlan>
 */
class SubscriptionPlanFactory extends Factory
{
    protected $model = SubscriptionPlan::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true).' Plan';

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name.'-'.Str::random(4)),
            'description' => fake()->sentence(),
            'price' => fake()->randomElement(['999.00', '1999.00', '4999.00']),
            'currency' => 'INR',
            'billing_cycle' => BillingCycle::Monthly,
            'trial_days' => 14,
            'features' => ['pos' => true, 'inventory' => true, 'reports' => true],
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the plan has no trial.
     */
    public function withoutTrial(): static
    {
        return $this->state(fn () => [
            'trial_days' => 0,
        ]);
    }

    /**
     * Indicate that the plan is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }
}
