<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;
use App\Policies\Concerns\ChecksMembership;

class LeadPolicy
{
    use ChecksMembership;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, Lead $lead): bool
    {
        return $this->isMember($user, $lead->workspace_id);
    }

    public function update(User $user, Lead $lead): bool
    {
        return $this->isMember($user, $lead->workspace_id);
    }

    public function delete(User $user, Lead $lead): bool
    {
        return $this->isMember($user, $lead->workspace_id);
    }
}
