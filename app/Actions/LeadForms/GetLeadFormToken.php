<?php

declare(strict_types=1);

namespace App\Actions\LeadForms;

use App\Models\Workspace;

class GetLeadFormToken
{
    public function __construct(private RegenerateLeadFormToken $regenerateLeadFormToken) {}

    /**
     * The agent's shareable form token, created the first time it's asked for.
     */
    public function handle(Workspace $workspace): string
    {
        return $workspace->lead_form_token ?? $this->regenerateLeadFormToken->handle($workspace);
    }
}
