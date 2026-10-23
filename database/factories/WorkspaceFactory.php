<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\WorkspaceRole;
use App\Enums\WorkspaceType;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Workspace>
 */
class WorkspaceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'type' => WorkspaceType::Insurance,
            'timezone' => 'Asia/Manila',
        ];
    }

    /** Makes the user the workspace's owner. */
    public function ownedBy(User $user): static
    {
        return $this->hasAttached($user, ['role' => WorkspaceRole::Owner], 'users');
    }

    /** Adds the user as a helping member. */
    public function withMember(User $user): static
    {
        return $this->hasAttached($user, ['role' => WorkspaceRole::Member], 'users');
    }
}
