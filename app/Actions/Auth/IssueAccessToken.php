<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;

class IssueAccessToken
{
    public function handle(User $user, string $deviceName): string
    {
        return $user->createToken($deviceName)->plainTextToken;
    }
}
