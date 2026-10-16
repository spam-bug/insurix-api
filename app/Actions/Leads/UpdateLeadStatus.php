<?php

namespace App\Actions\Leads;

use App\Enums\LeadStatus;
use App\Models\Lead;

class UpdateLeadStatus
{
    public function handle(Lead $lead, LeadStatus $status): Lead
    {
        $lead->update(['status' => $status]);

        return $lead;
    }
}
