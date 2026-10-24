<?php

declare(strict_types=1);

namespace App\Actions\Clients;

use App\Actions\Leads\RecordLeadActivity;
use App\Actions\Notifications\AnnounceWorkspaceChange;
use App\DataTransferObject\Clients\ClientProfileData;
use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
use App\Enums\LeadActivityType;
use App\Enums\LeadStatus;
use App\Models\Client;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PromoteLead
{
    public function __construct(private AnnounceWorkspaceChange $announceWorkspaceChange, private RecordLeadActivity $recordLeadActivity) {}

    /**
     * Turns the lead into a client owned by the same agent. The lead's details carry over and
     * the lead stays in the pipeline as Converted, so its activity remains the client's history.
     * The lead's follow-ups move to the client too, so they show and come due on the client.
     */
    public function handle(Lead $lead, ClientProfileData $profile, User $actor): Client
    {
        if ($lead->status === LeadStatus::Converted) {
            throw ValidationException::withMessages(['lead' => 'This lead is already a client.']);
        }

        $client = DB::transaction(function () use ($lead, $profile, $actor) {
            $previousStatus = $lead->status;

            $client = $lead->workspace->clients()->create([
                'lead_id' => $lead->id,
                'name' => $lead->name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'address' => $lead->address,
                'notes' => $lead->notes,
                ...$profile->toArray(),
            ]);

            $lead->update(['status' => LeadStatus::Converted]);
            $lead->followUps()->update(['client_id' => $client->id]);

            $this->recordLeadActivity->handle($lead, LeadActivityType::Promoted, $actor, properties: [
                'client_id' => $client->id,
                'previous_status' => $previousStatus->value,
            ]);

            return $client;
        });

        $this->announceWorkspaceChange->handle($lead->workspace_id, new WorkspaceChangeData(ChangeTopic::Clients, ChangeAction::Created, $client->id, $client->name));

        return $client;
    }
}
