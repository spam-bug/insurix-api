<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\LeadInterest;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'source' => fake()->randomElement(LeadSource::cases()),
            'interest' => fake()->randomElement(LeadInterest::cases()),
            'notes' => fake()->optional()->sentence(),
            'status' => LeadStatus::Prospect,
        ];
    }

    public function status(LeadStatus $status): static
    {
        return $this->state(fn (array $attributes) => ['status' => $status]);
    }
}
