<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ApplicationRequirement;
use App\Models\User;

class ApplicationRequirementPolicy
{
    public function update(User $user, ApplicationRequirement $requirement): bool
    {
        return $requirement->policy->user_id === $user->id;
    }

    public function delete(User $user, ApplicationRequirement $requirement): bool
    {
        return $requirement->policy->user_id === $user->id;
    }
}
