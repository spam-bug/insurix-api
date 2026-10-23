<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PaymentFrequency;
use App\Enums\PolicyType;
use App\Models\Product;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
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
            'name' => fake()->unique()->words(3, true),
            'type' => PolicyType::Life,
            'description' => fake()->optional()->sentence(),
            'commission_rate' => 30,
            'payment_frequency' => PaymentFrequency::Monthly,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }
}
