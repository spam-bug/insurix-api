<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

class RevokeSessions
{
    /**
     * Signs out one of the agent's other devices, or all of them when no session is given. The
     * current device stays signed in; it signs out with the normal log-out.
     *
     * @return int how many sessions were signed out
     */
    public function handle(User $agent, ?string $sessionId = null): int
    {
        $token = $agent->currentAccessToken();
        $currentTokenId = $token instanceof PersonalAccessToken ? $token->id : null;

        $revoked = DB::transaction(fn () => $agent->tokens()
            ->when($sessionId, fn ($query, $id) => $query->whereKey($id))
            ->when($currentTokenId, fn ($query, $id) => $query->whereKeyNot($id))
            ->delete());

        abort_if($sessionId !== null && $revoked === 0, 404);

        return $revoked;
    }
}
