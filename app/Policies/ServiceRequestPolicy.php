<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ServiceRequest;
use App\Models\User;
use App\Policies\Concerns\ChecksMembership;

class ServiceRequestPolicy
{
    use ChecksMembership;

    public function update(User $user, ServiceRequest $serviceRequest): bool
    {
        return $this->isMember($user, $serviceRequest->workspace_id);
    }

    public function delete(User $user, ServiceRequest $serviceRequest): bool
    {
        return $this->isMember($user, $serviceRequest->workspace_id);
    }
}
