<?php

declare(strict_types=1);

namespace App\Actions\FollowUps;

use App\Actions\Leads\ScoreLead;
use App\Models\FollowUp;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ReopenFollowUp
{
    public function __construct(private ScoreLead $scoreLead) {}

    /**
     * Undoes a completion, for a follow-up ticked off by mistake. The history entry stays.
     */
    public function handle(FollowUp $followUp): FollowUp
    {
        DB::transaction(function () use ($followUp) {
            $followUp->update(['completed_at' => null]);

            $this->scoreLead->handle($followUp->lead);
        });

        Cache::tags("workspace:{$followUp->workspace_id}")->flush();

        return $followUp;
    }
}
