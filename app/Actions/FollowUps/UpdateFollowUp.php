<?php

namespace App\Actions\FollowUps;

use App\DataTransferObjects\FollowUps\FollowUpData;
use App\Models\FollowUp;

class UpdateFollowUp
{
    public function handle(FollowUp $followUp, FollowUpData $data): FollowUp
    {
        $followUp->update($data->toAttributes());

        return $followUp;
    }
}
