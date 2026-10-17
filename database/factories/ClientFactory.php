<?php

namespace Database\Factories;

use App\Enums\CivilStatus;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'birthdate' => fake()->dateTimeBetween('-70 years', '-18 years'),
            'civil_status' => fake()->randomElement(CivilStatus::cases()),
            'occupation' => fake()->jobTitle(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
