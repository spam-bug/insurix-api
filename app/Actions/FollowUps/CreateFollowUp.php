<?php

declare(strict_types=1);

namespace App\Actions\FollowUps;

use App\Actions\Leads\ScoreLead;
use App\DataTransferObjects\FollowUps\FollowUpData;
use App\Models\FollowUp;
use App\Models\Lead;

class CreateFollowUp
{
    public function __construct(private ScoreLead $scoreLead) {}

    /**
     * Adds a follow-up for the lead, owned by the lead's agent. A follow-up already past due
     * lowers the lead's score, so the score is brought up to date.
     *
     * @param  array{policy_id?: int, reminder_key?: string}  $source  set for reminders created from a policy
     */
    public function handle(Lead $lead, FollowUpData $data, array $source = []): FollowUp
    {
        $followUp = $lead->followUps()->create([
            ...$data->toAttributes(),
            ...$source,
            'user_id' => $lead->user_id,
        ]);

        $this->scoreLead->handle($lead);

        return $followUp;
    }
}
