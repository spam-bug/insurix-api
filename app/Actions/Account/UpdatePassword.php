<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

class UpdatePassword
{
    /**
     * Sets a new password and signs the agent out everywhere else, keeping the session they
     * changed it from.
     */
    public function handle(User $agent, string $password): User
    {
        $token = $agent->currentAccessToken();
        $keepTokenId = $token instanceof PersonalAccessToken ? $token->id : null;

        DB::transaction(function () use ($agent, $password, $keepTokenId) {
            $agent->update(['password' => $password]);

            $agent->tokens()->when($keepTokenId, fn ($query, $id) => $query->whereKeyNot($id))->delete();
        });

        return $agent;
    }
}
