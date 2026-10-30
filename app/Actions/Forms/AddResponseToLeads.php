<?php

declare(strict_types=1);

namespace App\Actions\Forms;

use App\Actions\FollowUps\CreateFollowUp;
use App\Actions\Leads\RecordLeadActivity;
use App\Actions\Notifications\AnnounceWorkspaceChange;
use App\DataTransferObject\FollowUps\FollowUpData;
use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
use App\Enums\FollowUpType;
use App\Enums\FormResponseStatus;
use App\Enums\ImportField;
use App\Enums\LeadActivityType;
use App\Enums\LeadInterest;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Models\FormResponse;
use App\Models\Lead;
use App\Notifications\FormLeadNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class AddResponseToLeads
{
    public function __construct(
        private AnnounceWorkspaceChange $announceWorkspaceChange,
        private RecordLeadActivity $recordLeadActivity,
        private CreateFollowUp $createFollowUp,
    ) {}

    /**
     * Turns a response into a prospect from its linked fields, with a follow-up due now so it tops
     * the list for today, and tells everyone in the workspace. A response without a name and an
     * email or phone stays as it is. Nobody is signed in, so the activity has no actor.
     */
    public function handle(FormResponse $response): ?Lead
    {
        $details = $this->linkedAnswers($response);
        $name = $details[ImportField::Name->value] ?? trim(($details[ImportField::FirstName->value] ?? '').' '.($details[ImportField::LastName->value] ?? ''));

        if ($name === '' || (empty($details[ImportField::Email->value]) && empty($details[ImportField::Phone->value]))) {
            return null;
        }

        $workspace = $response->form->workspace;

        $lead = DB::transaction(function () use ($response, $workspace, $details, $name) {
            $lead = $workspace->leads()->create([
                'name' => $name,
                'email' => $details[ImportField::Email->value] ?? null,
                'phone' => $details[ImportField::Phone->value] ?? null,
                'address' => $details[ImportField::Address->value] ?? null,
                'interest' => LeadInterest::tryFrom($details[ImportField::Interest->value] ?? ''),
                'notes' => $details[ImportField::Notes->value] ?? null,
                'source' => LeadSource::ShareableForm,
                'status' => LeadStatus::Prospect,
            ]);

            $this->recordLeadActivity->handle($lead, LeadActivityType::Created, properties: ['source' => LeadSource::ShareableForm->value]);

            $this->createFollowUp->handle($lead, new FollowUpData(
                type: $lead->phone ? FollowUpType::Call : FollowUpType::Email,
                title: "Reach out to {$lead->name}: sent your form",
                dueAt: now()->toImmutable(),
            ));

            $response->update(['lead_id' => $lead->id, 'status' => FormResponseStatus::Added]);

            return $lead;
        });

        $this->announceWorkspaceChange->handle($workspace->id, new WorkspaceChangeData(ChangeTopic::Leads, ChangeAction::Created, $lead->id, $lead->name, message: "{$lead->name} just filled in your form"));

        Notification::send($workspace->users, new FormLeadNotification($lead));

        return $lead;
    }

    /**
     * The non-empty answers of linked fields, keyed by what they're linked to.
     *
     * @return array<string, string>
     */
    private function linkedAnswers(FormResponse $response): array
    {
        $details = [];

        foreach ($response->fields as $field) {
            $answer = $response->answers[$field['id']] ?? null;

            if (($field['link'] ?? null) !== null && is_string($answer) && trim($answer) !== '') {
                $details[$field['link']] = trim($answer);
            }
        }

        return $details;
    }
}
