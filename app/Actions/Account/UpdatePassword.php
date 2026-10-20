<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\Models\User;

class UpdatePassword
{
    /**
     * Sets a new password and signs the agent out everywhere else, keeping the session they
     * changed it from.
     */
    public function handle(User $agent, string $password, ?string $keepTokenId): User
    {
        $agent->update(['password' => $password]);

        $agent->tokens()->when($keepTokenId, fn ($query, $id) => $query->whereKeyNot($id))->delete();

        return $agent;
    }
}
