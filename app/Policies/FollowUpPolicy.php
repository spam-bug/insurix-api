<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\FollowUp;
use App\Models\User;
use App\Policies\Concerns\ChecksMembership;

class FollowUpPolicy
{
    use ChecksMembership;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function update(User $user, FollowUp $followUp): bool
    {
        return $this->isMember($user, $followUp->workspace_id);
    }

    public function delete(User $user, FollowUp $followUp): bool
    {
        return $this->isMember($user, $followUp->workspace_id);
    }
}
