<?php

declare(strict_types=1);

namespace App\Actions\FollowUps;

use App\DataTransferObject\FollowUps\FollowUpData;
use App\Models\FollowUp;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class UpdateFollowUp
{
    public function handle(FollowUp $followUp, FollowUpData $data): FollowUp
    {
        DB::transaction(fn () => $followUp->update($data->toArray()));

        Cache::tags("agent:{$followUp->user_id}")->flush();

        return $followUp;
    }
}
