<?php

declare(strict_types=1);

namespace App\Actions\Forms;

use App\Actions\FollowUps\CreateFollowUp;
use App\Actions\Imports\CleanImportRow;
use App\Actions\Imports\FillInPerson;
use App\Actions\Leads\RecordLeadActivity;
use App\Actions\Notifications\AnnounceWorkspaceChange;
use App\DataTransferObject\FollowUps\FollowUpData;
use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
use App\Enums\FollowUpType;
use App\Enums\FormResponseStatus;
use App\Enums\ImportField;
use App\Enums\ImportKind;
use App\Enums\LeadActivityType;
use App\Enums\LeadInterest;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Models\Client;
use App\Models\FormResponse;
use App\Models\Lead;
use App\Models\Workspace;
use App\Notifications\FormLeadNotification;
use App\Notifications\FormResponseNotification;
use App\Support\ImportValue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class AddResponseToLeads
{
    public function __construct(
        private AnnounceWorkspaceChange $announceWorkspaceChange,
        private RecordLeadActivity $recordLeadActivity,
        private CreateFollowUp $createFollowUp,
        private CleanImportRow $cleanImportRow,
        private FillInPerson $fillInPerson,
    ) {}

    /**
     * Turns a response into a prospect from its linked fields, with a follow-up due now so it tops
     * the list for today. Someone already in the workspace (a client first, then a lead, by email
     * or phone) isn't added twice: their answers fill in what their record is missing, and they
     * get the follow-up instead. Either way the response links to them and everyone is told.
     * A response without a name and an email or phone stays as it is. Nobody is signed in, so
     * the activity has no actor.
     */
    public function handle(FormResponse $response): Client|Lead|null
    {
        $details = $this->linkedAnswers($response);
        $name = $details[ImportField::Name->value] ?? trim(($details[ImportField::FirstName->value] ?? '').' '.($details[ImportField::LastName->value] ?? ''));

        if ($name === '' || (empty($details[ImportField::Email->value]) && empty($details[ImportField::Phone->value]))) {
            return null;
        }

        $workspace = $response->form->workspace;
        $existing = $this->existingPerson($workspace, $details);

        return $existing === null
            ? $this->createLead($response, $workspace, $details, $name)
            : $this->fillIn($response, $existing, $details);
    }

    /**
     * @param  array<string, string>  $details
     */
    private function createLead(FormResponse $response, Workspace $workspace, array $details, string $name): Lead
    {
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
            $this->followUp($lead, "Reach out to {$lead->name}: sent your form");
            $response->update(['lead_id' => $lead->id, 'status' => FormResponseStatus::Added]);

            return $lead;
        });

        $this->announceWorkspaceChange->handle($workspace->id, new WorkspaceChangeData(ChangeTopic::Leads, ChangeAction::Created, $lead->id, $lead->name, message: "{$lead->name} just filled in your form"));

        Notification::send($workspace->users, new FormLeadNotification($lead));

        return $lead;
    }

    /**
     * Cleans the answers the way an import would (phones, dates, choices) and fills in only the
     * details the record is missing; answers that can't be read are left out.
     *
     * @param  array<string, string>  $details
     */
    private function fillIn(FormResponse $response, Client|Lead $person, array $details): Client|Lead
    {
        $kind = $person instanceof Client ? ImportKind::Clients : ImportKind::Leads;
        $fields = array_values(array_intersect(array_keys($details), array_column(ImportField::for($kind), 'value')));
        $cleaned = $this->cleanImportRow->handle($kind, array_flip($fields), array_map(fn (string $field) => $details[$field], $fields));
        $data = array_diff_key($cleaned['data'], $cleaned['errors'], ['name' => true]);

        $filled = DB::transaction(function () use ($response, $person, $data) {
            $filled = $this->fillInPerson->handle($person, $data);
            $this->followUp($person, "Reach out to {$person->name}: sent your form again");
            $response->update([$person instanceof Client ? 'client_id' : 'lead_id' => $person->id, 'status' => FormResponseStatus::Added]);

            return $filled;
        });

        $topic = $person instanceof Client ? ChangeTopic::Clients : ChangeTopic::Leads;
        $this->announceWorkspaceChange->handle($person->workspace_id, new WorkspaceChangeData($topic, ChangeAction::Updated, $person->id, $person->name, message: "{$person->name} filled in your form again"));

        Notification::send($response->form->workspace->users, new FormResponseNotification($response, $response->form->name, $person, $filled));

        return $person;
    }

    private function followUp(Client|Lead $person, string $title): void
    {
        $this->createFollowUp->handle($person, new FollowUpData(
            type: $person->phone ? FollowUpType::Call : FollowUpType::Email,
            title: $title,
            dueAt: now()->toImmutable(),
        ));
    }

    /**
     * A client, else a lead, with the same email or phone. Phones are compared by their digits,
     * so "0917 555 0100" and "+63 917 555 0100" are the same person; the query only narrows
     * the candidates by their last digits.
     *
     * @param  array<string, string>  $details
     */
    private function existingPerson(Workspace $workspace, array $details): Client|Lead|null
    {
        $email = isset($details[ImportField::Email->value]) ? mb_strtolower($details[ImportField::Email->value]) : null;
        $phoneKey = isset($details[ImportField::Phone->value]) ? ImportValue::phoneKey($details[ImportField::Phone->value]) : null;
        $phoneKey = $phoneKey !== null && strlen($phoneKey) >= 7 ? $phoneKey : null;

        if ($email === null && $phoneKey === null) {
            return null;
        }

        foreach ([$workspace->clients(), $workspace->leads()] as $query) {
            $candidates = $query
                ->where(fn ($where) => $where
                    ->when($email, fn ($when) => $when->orWhereRaw('lower(email) = ?', [$email]))
                    ->when($phoneKey, fn ($when) => $when->orWhere('phone', 'like', '%'.substr($phoneKey, -4))))
                ->get();
            $match = $candidates->first(fn (Client|Lead $person) => ($email !== null && mb_strtolower((string) $person->email) === $email)
                || ($phoneKey !== null && $person->phone !== null && ImportValue::phoneKey($person->phone) === $phoneKey));

            if ($match !== null) {
                return $match;
            }
        }

        return null;
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
