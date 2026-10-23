<?php

declare(strict_types=1);

namespace App\Actions\Leads;

use App\Enums\LeadActivityType;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class AddLeadNote
{
    public function __construct(private RecordLeadActivity $recordLeadActivity) {}

    public function handle(Lead $lead, string $body, User $actor): LeadActivity
    {
        $activity = $this->recordLeadActivity->handle($lead, LeadActivityType::Note, $actor, $body);

        Cache::tags("workspace:{$lead->workspace_id}")->flush();

        return $activity;
    }
}
