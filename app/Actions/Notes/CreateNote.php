<?php

declare(strict_types=1);

namespace App\Actions\Notes;

use App\Actions\Leads\RecordLeadActivity;
use App\Actions\Mentions\RecordMentions;
use App\Actions\Notifications\AnnounceWorkspaceChange;
use App\DataTransferObject\Notes\NoteData;
use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
use App\Enums\LeadActivityType;
use App\Models\Client;
use App\Models\Lead;
use App\Models\Note;
use App\Models\Policy;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\MentionedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class CreateNote
{
    public function __construct(
        private AnnounceWorkspaceChange $announceWorkspaceChange,
        private RecordLeadActivity $recordLeadActivity,
        private RecordMentions $recordMentions,
    ) {}

    /**
     * Saves the note with everyone it concerns: a policy note also belongs to the client and
     * their lead, a client note to their lead. A note with a lead gets an entry in the lead's
     * timeline, which also counts as being in touch with them. Mentioned members are told.
     */
    public function handle(Workspace $workspace, NoteData $data, User $author): Note
    {
        $policy = $data->policyId ? Policy::findOrFail($data->policyId) : null;
        $client = $policy?->client ?? ($data->clientId ? Client::findOrFail($data->clientId) : null);
        $lead = $client?->lead ?? ($data->leadId ? Lead::findOrFail($data->leadId) : null);
        $client ??= $lead?->client;

        [$note, $mentioned] = DB::transaction(function () use ($workspace, $data, $author, $policy, $client, $lead) {
            $activity = $lead ? $this->recordLeadActivity->handle($lead, LeadActivityType::Note, $author) : null;

            $note = $workspace->notes()->create([
                'user_id' => $author->id,
                'lead_id' => $lead?->id,
                'client_id' => $client?->id,
                'policy_id' => $policy?->id,
                'lead_activity_id' => $activity?->id,
                'body' => $data->body,
            ]);

            return [$note, $this->recordMentions->handle($note, $author)];
        });

        Notification::send($mentioned, new MentionedNotification($note, $author));

        $this->announceWorkspaceChange->handle($workspace->id, new WorkspaceChangeData(ChangeTopic::Notes, ChangeAction::Created, $note->id, Str::limit($note->body, 40)));

        return $note;
    }
}
