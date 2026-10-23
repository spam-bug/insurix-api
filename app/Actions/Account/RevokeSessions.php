<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\Models\User;
use Illuminate\Support\Facades\DB;

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
        $revoked = DB::transaction(fn () => DB::table('sessions')
            ->where('user_id', $agent->id)
            ->where('id', '!=', session()->getId())
            ->when($sessionId, fn ($query, $id) => $query->where('id', $id))
            ->delete());

        abort_if($sessionId !== null && $revoked === 0, 404);

        return $revoked;
    }
}
