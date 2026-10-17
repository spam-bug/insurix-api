<?php

namespace App\Actions\FollowUps;

use App\Models\FollowUp;

class ReopenFollowUp
{
    /**
     * Undoes a completion, for a follow-up ticked off by mistake. The history entry stays.
     */
    public function handle(FollowUp $followUp): FollowUp
    {
        $followUp->update(['completed_at' => null]);

        return $followUp;
    }
}
