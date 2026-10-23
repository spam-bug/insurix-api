<?php

declare(strict_types=1);

namespace App\Actions\FollowUps;

use App\Actions\Leads\ScoreLead;
use App\Models\FollowUp;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DeleteFollowUp
{
    public function __construct(private ScoreLead $scoreLead) {}

    public function handle(FollowUp $followUp): void
    {
        DB::transaction(function () use ($followUp) {
            $lead = $followUp->lead;

            $followUp->delete();

            $this->scoreLead->handle($lead);
        });

        Cache::tags("workspace:{$followUp->workspace_id}")->flush();
    }
}
