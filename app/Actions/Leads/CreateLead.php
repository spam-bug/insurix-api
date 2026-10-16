<?php

namespace App\Actions\Leads;

use App\DataTransferObjects\Leads\LeadData;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\User;

class CreateLead
{
    /**
     * Every new lead starts as a prospect, whatever the caller sends.
     */
    public function handle(User $agent, LeadData $data): Lead
    {
        return $agent->leads()->create([
            ...$data->toAttributes(),
            'status' => LeadStatus::Prospect,
        ]);
    }
}
