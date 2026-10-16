<?php

namespace App\Actions\LeadForms;

use App\Actions\Leads\RecordLeadActivity;
use App\DataTransferObjects\Leads\LeadData;
use App\Enums\LeadActivityType;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SubmitLeadForm
{
    public function __construct(private RecordLeadActivity $recordLeadActivity) {}

    /**
     * A prospective client filled in the agent's shareable form: create a prospect owned by
     * that agent. Nobody is signed in, so the activity has no actor.
     */
    public function handle(User $agent, LeadData $data): Lead
    {
        return DB::transaction(function () use ($agent, $data) {
            $lead = $agent->leads()->create([
                ...$data->toAttributes(),
                'source' => LeadSource::ShareableForm,
                'status' => LeadStatus::Prospect,
            ]);

            $this->recordLeadActivity->handle($lead, LeadActivityType::Created, properties: ['source' => LeadSource::ShareableForm->value]);

            return $lead;
        });
    }
}
