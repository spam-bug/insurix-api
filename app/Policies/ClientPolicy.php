<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Client;
use App\Models\User;
use App\Policies\Concerns\ChecksMembership;

class ClientPolicy
{
    use ChecksMembership;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Client $client): bool
    {
        return $this->isMember($user, $client->workspace_id);
    }

    public function update(User $user, Client $client): bool
    {
        return $this->isMember($user, $client->workspace_id);
    }
}
