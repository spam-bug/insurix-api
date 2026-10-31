<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Feature;
use App\Models\Automation;
use App\Models\User;
use App\Models\Workspace;
use App\Policies\Concerns\ChecksMembership;
use Laravel\Pennant\Feature as Pennant;

/** Automations are a Premium feature: anyone in a Premium workspace manages them. */
class AutomationPolicy
{
    use ChecksMembership;

    public function viewAny(User $user, Workspace $workspace): bool
    {
        return $this->isMember($user, $workspace->id) && Pennant::for($workspace)->active(Feature::Automations);
    }

    public function create(User $user, Workspace $workspace): bool
    {
        return $this->viewAny($user, $workspace);
    }

    public function update(User $user, Automation $automation): bool
    {
        return $this->viewAny($user, $automation->workspace);
    }

    public function delete(User $user, Automation $automation): bool
    {
        return $this->viewAny($user, $automation->workspace);
    }
}
