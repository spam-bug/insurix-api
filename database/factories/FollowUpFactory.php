<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\FollowUpType;
use App\Models\Client;
use App\Models\FollowUp;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FollowUp>
 */
class FollowUpFactory extends Factory
{
    /**
     * Define the model's default state. The owner follows the lead's owner.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lead_id' => Lead::factory(),
            'workspace_id' => fn (array $attributes) => Lead::find($attributes['lead_id'])->workspace_id,
            'type' => fake()->randomElement(FollowUpType::cases()),
            'title' => fake()->sentence(4),
            'notes' => fake()->optional()->sentence(),
            'due_at' => now()->addDays(2),
            'completed_at' => null,
        ];
    }

    public function dueAt(\DateTimeInterface $dueAt): static
    {
        return $this->state(fn (array $attributes) => ['due_at' => $dueAt]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => ['completed_at' => now()]);
    }

    /** A follow-up on a client who has no lead, such as one added directly. */
    public function forClient(Client $client): static
    {
        return $this->state(fn (array $attributes) => ['lead_id' => null, 'client_id' => $client->id, 'workspace_id' => $client->workspace_id]);
    }
}
