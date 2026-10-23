<?php

declare(strict_types=1);

namespace App\Actions\Leads;

use App\DataTransferObject\Leads\LeadData;
use App\Enums\LeadActivityType;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CreateLead
{
    public function __construct(private RecordLeadActivity $recordLeadActivity) {}

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

        Cache::tags("workspace:{$workspace->id}")->flush();

        return $lead;
    }
}
