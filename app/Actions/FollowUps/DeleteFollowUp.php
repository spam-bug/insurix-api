<?php

namespace App\Actions\FollowUps;

use App\Models\FollowUp;

class DeleteFollowUp
{
    public function handle(FollowUp $followUp): void
    {
        $followUp->delete();
    }
}
