<?php

declare(strict_types=1);

namespace App\Actions\LeadForms;

use App\Models\User;

class GetLeadFormToken
{
    public function __construct(private RegenerateLeadFormToken $regenerateLeadFormToken) {}

    /**
     * The agent's shareable form token, created the first time it's asked for.
     */
    public function handle(User $agent): string
    {
        return $agent->lead_form_token ?? $this->regenerateLeadFormToken->handle($agent);
    }
}
