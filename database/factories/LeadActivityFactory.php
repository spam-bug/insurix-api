<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\LeadActivityType;
use App\Models\Lead;
use App\Models\LeadActivity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeadActivity>
 */
class LeadActivityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lead_id' => Lead::factory(),
            'user_id' => fn (array $attributes) => Lead::find($attributes['lead_id'])?->workspace->users()->first()?->id,
            'type' => LeadActivityType::Note,
            'body' => fake()->sentence(),
            'properties' => null,
        ];
    }
}
