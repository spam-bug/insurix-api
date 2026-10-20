<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class RevokeSessions
{
    /**
     * Signs out one other device, or every device but the current one when $tokenId is null.
     *
     * @return int how many sessions were signed out
     */
    public function handle(User $agent, ?string $tokenId, ?string $currentTokenId): int
    {
        return DB::transaction(fn () => $agent->tokens()
            ->when($tokenId, fn ($query, $id) => $query->whereKey($id))
            ->when($currentTokenId, fn ($query, $id) => $query->whereKeyNot($id))
            ->delete());
    }
}
