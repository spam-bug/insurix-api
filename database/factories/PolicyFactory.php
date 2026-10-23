<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PaymentFrequency;
use App\Enums\PolicyStatus;
use App\Enums\PolicyType;
use App\Enums\RenewalTerm;
use App\Models\Client;
use App\Models\Policy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Policy>
 */
class PolicyFactory extends Factory
{
    /**
     * Define the model's default state. The owner follows the client's owner.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'workspace_id' => fn (array $attributes) => Client::find($attributes['client_id'])->workspace_id,
            'product_name' => fake()->words(2, true),
            'policy_number' => fake()->unique()->numerify('PH-########'),
            'type' => PolicyType::Life,
            'status' => PolicyStatus::InForce,
            'premium' => 2500,
            'payment_frequency' => PaymentFrequency::Monthly,
            'commission_rate' => 30,
            'effective_date' => now()->subMonths(3)->toDateString(),
            'renewal_term' => RenewalTerm::Custom,
            'renewal_date' => now()->addMonths(9)->toDateString(),
            'commission_received_at' => null,
            'notes' => null,
        ];
    }

    public function status(PolicyStatus $status): static
    {
        return $this->state(fn (array $attributes) => ['status' => $status]);
    }
}
