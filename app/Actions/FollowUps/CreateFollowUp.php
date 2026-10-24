<?php

declare(strict_types=1);

namespace App\Actions\FollowUps;

use App\Actions\Leads\ScoreLead;
use App\Actions\Notifications\AnnounceWorkspaceChange;
use App\DataTransferObject\FollowUps\FollowUpData;
use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
use App\Models\Client;
use App\Models\FollowUp;
use App\Models\Lead;
use App\Models\Policy;
use Illuminate\Support\Facades\DB;

class CreateFollowUp
{
    public function __construct(private AnnounceWorkspaceChange $announceWorkspaceChange, private ScoreLead $scoreLead) {}

    /**
     * Adds a follow-up for a lead or a client. It's linked to both when the person is both (a
     * promoted lead), so it shows on the lead and the client. A follow-up already past due lowers
     * the lead's score, so the score is brought up to date. Reminders created from a policy carry
     * the policy and a key, so each is only made once.
     */
    public function handle(Lead|Client $person, FollowUpData $data, ?Policy $policy = null, ?string $reminderKey = null): FollowUp
    {
        $lead = $person instanceof Lead ? $person : $person->lead;

        $followUp = DB::transaction(function () use ($person, $lead, $data, $policy, $reminderKey) {
            $followUp = FollowUp::create([
                ...$data->toArray(),
                'workspace_id' => $person->workspace_id,
                'lead_id' => $lead?->id,
                'client_id' => $person instanceof Client ? $person->id : $person->client?->id,
                'policy_id' => $policy?->id,
                'reminder_key' => $reminderKey,
            ]);

            if ($lead) {
                $this->scoreLead->handle($lead);
            }

            return $followUp;
        });

        $this->announceWorkspaceChange->handle($person->workspace_id, new WorkspaceChangeData(ChangeTopic::FollowUps, ChangeAction::Created, $followUp->id, $followUp->title));

        return $followUp;
    }
}
