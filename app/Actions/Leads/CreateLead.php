<?php

declare(strict_types=1);

namespace App\Actions\Leads;

use App\Actions\Notifications\AnnounceWorkspaceChange;
use App\DataTransferObject\Leads\LeadData;
use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
use App\Enums\LeadActivityType;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

class CreateLead
{
    public function __construct(private AnnounceWorkspaceChange $announceWorkspaceChange, private RecordLeadActivity $recordLeadActivity) {}

    /**
     * Every new lead starts as a prospect, whatever the caller sends.
     */
    public function handle(Workspace $workspace, LeadData $data, User $actor): Lead
    {
        $lead = DB::transaction(function () use ($workspace, $data, $actor) {
            $lead = $workspace->leads()->create([
                ...$data->toArray(),
                'status' => LeadStatus::Prospect,
            ]);

            $this->recordLeadActivity->handle($lead, LeadActivityType::Created, $actor, properties: ['source' => $lead->source->value]);

            return $lead;
        });

        $this->announceWorkspaceChange->handle($workspace->id, new WorkspaceChangeData(ChangeTopic::Leads, ChangeAction::Created, $lead->id, $lead->name));

        return $lead;
    }
}
