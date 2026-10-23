<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\Models\User;
use Illuminate\Support\Collection;
use Laravel\Sanctum\PersonalAccessToken;

class ListSessions
{
    /**
     * Where the agent is signed in: one entry per access token, most recently used first.
     *
     * @return Collection<int, array{id: int, device: string, last_used_at: string|null, created_at: string|null, current: bool}>
     */
    public function handle(User $agent, ?string $currentTokenId): Collection
    {
        return $agent->tokens()
            ->orderByDesc('last_used_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (PersonalAccessToken $token) => [
                'id' => $token->id,
                'device' => $token->name,
                'last_used_at' => $token->last_used_at?->toIso8601String(),
                'created_at' => $token->created_at?->toIso8601String(),
                'current' => $token->id === $currentTokenId,
            ]);
    }
}
