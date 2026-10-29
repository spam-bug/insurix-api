<?php

declare(strict_types=1);

namespace App\Actions\Leads;

use App\Actions\Mentions\RecordMentions;
use App\Actions\Notifications\AnnounceWorkspaceChange;
use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
use App\Enums\LeadActivityType;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\User;
use App\Notifications\MentionedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class AddLeadNote
{
    public function __construct(
        private AnnounceWorkspaceChange $announceWorkspaceChange,
        private RecordLeadActivity $recordLeadActivity,
        private RecordMentions $recordMentions,
    ) {}

    public function handle(Lead $lead, string $body, User $actor): LeadActivity
    {
        [$note, $mentioned] = DB::transaction(function () use ($lead, $body, $actor) {
            $note = $this->recordLeadActivity->handle($lead, LeadActivityType::Note, $actor, $body);

            return [$note, $this->recordMentions->handle($note, $lead->workspace_id, $actor)];
        });

        Notification::send($mentioned, new MentionedNotification($lead, $note, $actor));

        $this->announceWorkspaceChange->handle($lead->workspace_id, new WorkspaceChangeData(ChangeTopic::Leads, ChangeAction::Updated, $lead->id, $lead->name));

        return $note;
    }
}
