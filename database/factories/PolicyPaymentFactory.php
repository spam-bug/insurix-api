<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Policy;
use App\Models\PolicyPayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PolicyPayment>
 */
class PolicyPaymentFactory extends Factory
{
    /**
     * Define the model's default state: this month's premium, paid on time, commission not in yet.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'policy_id' => Policy::factory(),
            'workspace_id' => fn (array $attributes) => Policy::find($attributes['policy_id'])->workspace_id,
            'due_on' => now()->toDateString(),
            'paid_on' => now()->toDateString(),
            'amount' => 2500,
            'commission_expected' => 750,
            'commission_received' => null,
            'commission_received_on' => null,
            'notes' => null,
        ];
    }
}
