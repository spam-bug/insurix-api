<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ServiceRequestStatus;
use App\Enums\ServiceRequestType;
use App\Models\Client;
use App\Models\ServiceRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceRequest>
 */
class ServiceRequestFactory extends Factory
{
    /**
     * Define the model's default state. The workspace follows the client's.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'workspace_id' => fn (array $attributes) => Client::find($attributes['client_id'])->workspace_id,
            'type' => ServiceRequestType::Inquiry,
            'status' => ServiceRequestStatus::Open,
            'title' => fake()->sentence(4),
            'details' => null,
            'due_on' => null,
        ];
    }

    public function claim(float $amount = 25_000): static
    {
        return $this->state(fn (array $attributes) => ['type' => ServiceRequestType::Claim, 'claim_amount' => $amount, 'insurer_reference' => 'CLM-'.fake()->numerify('######')]);
    }

    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => ['status' => ServiceRequestStatus::Resolved, 'resolved_at' => now()]);
    }
}
