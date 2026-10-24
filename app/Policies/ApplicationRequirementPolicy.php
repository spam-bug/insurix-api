<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ApplicationRequirement;
use App\Models\User;
use App\Policies\Concerns\ChecksMembership;

class ApplicationRequirementPolicy
{
    use ChecksMembership;

    public function update(User $user, ApplicationRequirement $requirement): bool
    {
        return $this->isMember($user, $requirement->policy->workspace_id);
    }

    public function delete(User $user, ApplicationRequirement $requirement): bool
    {
        return $this->isMember($user, $requirement->policy->workspace_id);
    }
}
