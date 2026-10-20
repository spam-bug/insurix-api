<?php

declare(strict_types=1);

namespace App\Actions\LeadForms;

use App\Actions\FollowUps\CreateFollowUp;
use App\Actions\Leads\RecordLeadActivity;
use App\Actions\Notifications\NotifyAgent;
use App\DataTransferObject\FollowUps\FollowUpData;
use App\DataTransferObject\Leads\LeadFormSubmissionData;
use App\Enums\FollowUpType;
use App\Enums\LeadActivityType;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SubmitLeadForm
{
    public function __construct(
        private RecordLeadActivity $recordLeadActivity,
        private CreateFollowUp $createFollowUp,
        private NotifyAgent $notifyAgent,
    ) {}

    /**
     * A prospective client filled in the agent's shareable form: create a prospect owned by
     * that agent, and a follow-up due now so it tops the agent's list for today. Nobody is
     * signed in, so the activity has no actor. A filled-in honeypot is dropped silently, so
     * bots get the same answer as people.
     */
    public function handle(User $agent, LeadFormSubmissionData $data): ?Lead
    {
        if ($data->website !== null) {
            return null;
        }

        return DB::transaction(function () use ($agent, $data) {
            $lead = $agent->leads()->create([
                'name' => $data->name,
                'email' => $data->email,
                'phone' => $data->phone,
                'interest' => $data->interest,
                'notes' => $data->message,
                'source' => LeadSource::ShareableForm,
                'status' => LeadStatus::Prospect,
            ]);

            $this->recordLeadActivity->handle($lead, LeadActivityType::Created, properties: ['source' => LeadSource::ShareableForm->value]);

            $this->createFollowUp->handle($lead, new FollowUpData(
                type: $lead->phone ? FollowUpType::Call : FollowUpType::Email,
                title: "Reach out to {$lead->name}: sent your form",
                dueAt: now()->toImmutable(),
                notes: null,
            ));

            $this->notifyAgent->handle($agent->id, 'leads', "{$lead->name} just filled in your form");

            return $lead;
        });
    }
}
