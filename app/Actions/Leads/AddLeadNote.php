<?php

declare(strict_types=1);

namespace App\Actions\Leads;

use App\Actions\Notifications\AnnounceWorkspaceChange;
use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
use App\Enums\LeadActivityType;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\User;

class AddLeadNote
{
    public function __construct(private AnnounceWorkspaceChange $announceWorkspaceChange, private RecordLeadActivity $recordLeadActivity) {}

    public function handle(Lead $lead, string $body, User $actor): LeadActivity
    {
        $activity = $this->recordLeadActivity->handle($lead, LeadActivityType::Note, $actor, $body);

        $this->announceWorkspaceChange->handle($lead->workspace_id, new WorkspaceChangeData(ChangeTopic::Leads, ChangeAction::Updated, $lead->id, $lead->name));

        return $activity;
    }
}
