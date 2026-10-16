<?php

namespace App\Actions\Leads;

use App\DataTransferObjects\Leads\LeadData;
use App\Models\Lead;

class UpdateLead
{
    public function handle(Lead $lead, LeadData $data): Lead
    {
        $lead->update($data->toAttributes());

        return $lead;
    }
}
