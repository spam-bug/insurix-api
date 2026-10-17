<?php

namespace App\Actions\FollowUps;

use App\Actions\Leads\ScoreLead;
use App\Models\FollowUp;

class ReopenFollowUp
{
    public function __construct(private ScoreLead $scoreLead) {}

    /**
     * Undoes a completion, for a follow-up ticked off by mistake. The history entry stays.
     */
    public function handle(FollowUp $followUp): FollowUp
    {
        $followUp->update(['completed_at' => null]);

        $this->scoreLead->handle($followUp->lead);

        return $followUp;
    }
}
