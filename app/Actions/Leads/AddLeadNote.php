<?php

namespace App\Actions\Leads;

use App\Enums\LeadActivityType;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\User;

class AddLeadNote
{
    public function __construct(private RecordLeadActivity $recordLeadActivity) {}

    public function handle(Lead $lead, string $body, User $actor): LeadActivity
    {
        return $this->recordLeadActivity->handle($lead, LeadActivityType::Note, $actor, $body);
    }
}
