<?php

declare(strict_types=1);

namespace App\Actions\Clients;

use App\Actions\Leads\RecordLeadActivity;
use App\DataTransferObject\Clients\ClientProfileData;
use App\Enums\LeadActivityType;
use App\Enums\LeadStatus;
use App\Models\Client;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PromoteLead
{
    public function __construct(private RecordLeadActivity $recordLeadActivity) {}

    /**
     * Turns the lead into a client owned by the same agent. The lead's details carry over and
     * the lead stays in the pipeline as Converted, so its activity remains the client's history.
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

            $this->recordLeadActivity->handle($lead, LeadActivityType::Promoted, $actor, properties: [
                'client_id' => $client->id,
                'previous_status' => $previousStatus->value,
            ]);

            return $client;
        });

        Cache::tags("workspace:{$lead->workspace_id}")->flush();

        return $client;
    }
}
