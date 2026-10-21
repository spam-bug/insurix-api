<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ListSessions
{
    /**
     * Where the agent is signed in: one entry per browser session, most recently active first.
     *
     * @return Collection<int, array{id: string, user_agent: string|null, ip_address: string|null, last_active_at: string, current: bool}>
     */
    public function handle(User $agent): Collection
    {
        return DB::table('sessions')
            ->where('user_id', $agent->id)
            ->orderByDesc('last_activity')
            ->get(['id', 'user_agent', 'ip_address', 'last_activity'])
            ->map(fn (object $session) => [
                'id' => $session->id,
                'user_agent' => $session->user_agent,
                'ip_address' => $session->ip_address,
                'last_active_at' => CarbonImmutable::createFromTimestamp($session->last_activity)->toIso8601String(),
                'current' => $session->id === session()->getId(),
            ]);
    }
}
