<?php

declare(strict_types=1);

namespace App\Actions\FollowUps;

use App\DataTransferObject\FollowUps\FollowUpData;
use App\Models\FollowUp;
use Illuminate\Support\Facades\DB;

class UpdateFollowUp
{
    public function handle(FollowUp $followUp, FollowUpData $data): FollowUp
    {
        DB::transaction(fn () => $followUp->update($data->toArray()));

        return $followUp;
    }
}
