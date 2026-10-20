<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\Models\User;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;

class RegenerateRecoveryCodes
{
    public function __construct(private GenerateNewRecoveryCodes $generateNewRecoveryCodes) {}

    /**
     * Replaces every recovery code, so ones the agent may have lost stop working.
     *
     * @return array<int, string>
     */
    public function handle(User $agent): array
    {
        ($this->generateNewRecoveryCodes)($agent);

        return $agent->fresh()->recoveryCodes();
    }
}
