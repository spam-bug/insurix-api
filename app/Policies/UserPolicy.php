<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Agents only ever change their own account.
     */
    public function update(User $user, User $account): bool
    {
        return $user->is($account);
    }

    /** Agents only ever delete their own account. */
    public function delete(User $user, User $account): bool
    {
        return $user->is($account);
    }
}
