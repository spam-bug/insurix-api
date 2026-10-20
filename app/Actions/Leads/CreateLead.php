<?php

declare(strict_types=1);

namespace App\Actions\Leads;

use App\DataTransferObject\Leads\LeadData;
use App\Enums\LeadActivityType;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateLead
{
    public function __construct(private RecordLeadActivity $recordLeadActivity) {}

    /**
     * Every new lead starts as a prospect, whatever the caller sends.
     */
    public function handle(User $agent, LeadData $data): Lead
    {
        return DB::transaction(function () use ($agent, $data) {
            $lead = $agent->leads()->create([
                ...$data->toAttributes(),
                'status' => LeadStatus::Prospect,
            ]);

            $this->recordLeadActivity->handle($lead, LeadActivityType::Created, $agent, properties: ['source' => $lead->source->value]);

            return $lead;
        });
    }
}
