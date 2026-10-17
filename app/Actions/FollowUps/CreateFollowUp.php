<?php

namespace App\Actions\FollowUps;

use App\DataTransferObjects\FollowUps\FollowUpData;
use App\Models\FollowUp;
use App\Models\Lead;

class CreateFollowUp
{
    /**
     * Adds a follow-up for the lead, owned by the lead's agent.
     */
    public function handle(Lead $lead, FollowUpData $data): FollowUp
    {
        return $lead->followUps()->create([
            ...$data->toAttributes(),
            'user_id' => $lead->user_id,
        ]);
    }
}
