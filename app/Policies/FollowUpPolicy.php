<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\FollowUp;
use App\Models\User;

class FollowUpPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function update(User $user, FollowUp $followUp): bool
    {
        return $followUp->user_id === $user->id;
    }

    public function delete(User $user, FollowUp $followUp): bool
    {
        return $followUp->user_id === $user->id;
    }
}
