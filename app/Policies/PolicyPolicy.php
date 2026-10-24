<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Policy;
use App\Models\User;
use App\Policies\Concerns\ChecksMembership;

class PolicyPolicy
{
    use ChecksMembership;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Policy $policy): bool
    {
        return $this->isMember($user, $policy->workspace_id);
    }

    public function update(User $user, Policy $policy): bool
    {
        return $this->isMember($user, $policy->workspace_id);
    }

    public function delete(User $user, Policy $policy): bool
    {
        return $this->isMember($user, $policy->workspace_id);
    }
}
