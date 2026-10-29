<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Note;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Note>
 */
class NoteFactory extends Factory
{
    /**
     * A free note; link it with for() or state().
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'user_id' => User::factory(),
            'body' => fake()->sentence(),
        ];
    }
}
