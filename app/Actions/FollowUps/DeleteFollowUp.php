<?php

declare(strict_types=1);

namespace App\Actions\FollowUps;

use App\Actions\Leads\ScoreLead;
use App\Models\FollowUp;

class DeleteFollowUp
{
    public function __construct(private ScoreLead $scoreLead) {}

    public function handle(FollowUp $followUp): void
    {
        $lead = $followUp->lead;

        $followUp->delete();

        $this->scoreLead->handle($lead);
    }
}
