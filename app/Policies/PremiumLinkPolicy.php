<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PremiumLink;
use App\Models\User;
use Illuminate\Support\Str;

class PremiumLinkPolicy
{
    /** A link made for one email only works for that account; any other link works for anyone. */
    public function claim(User $user, PremiumLink $premiumLink): bool
    {
        return $premiumLink->email === null || Str::lower($user->email) === Str::lower($premiumLink->email);
    }
}
