<?php

namespace App\Actions\Account;

use App\Models\User;

class UpdateLeadFormBio
{
    /**
     * The short introduction shown at the top of the agent's shareable form.
     */
    public function handle(User $agent, ?string $bio): User
    {
        $agent->update(['bio' => $bio]);

        return $agent;
    }
}
