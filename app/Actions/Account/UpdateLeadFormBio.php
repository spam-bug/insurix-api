<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateLeadFormBio
{
    /**
     * The short introduction shown at the top of the agent's shareable form.
     */
    public function handle(User $agent, ?string $bio): User
    {
        DB::transaction(fn () => $agent->update(['bio' => $bio]));

        return $agent;
    }
}
