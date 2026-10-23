<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Policy;
use App\Models\User;

class PolicyPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Policy $policy): bool
    {
        return $policy->user_id === $user->id;
    }

    public function update(User $user, Policy $policy): bool
    {
        return $policy->user_id === $user->id;
    }

    public function delete(User $user, Policy $policy): bool
    {
        return $policy->user_id === $user->id;
    }
}
