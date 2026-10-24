<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Leads\ScoreLead;
use App\Actions\Notifications\NotifyWorkspace;
use App\Models\Lead;
use App\Models\Workspace;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Scores change with time as well as activity (days since contact, follow-ups falling due),
 * so every lead is rescored daily.
 */
#[Signature('leads:rescore')]
#[Description('Recalculate every lead’s likelihood-to-convert score')]
class RescoreLeads extends Command
{
    public function handle(ScoreLead $scoreLead, NotifyWorkspace $notifyWorkspace): int
    {
        $scored = 0;

        Workspace::query()->whereHas('leads')->each(function (Workspace $workspace) use ($scoreLead, $notifyWorkspace, &$scored) {
            $sourceRates = null;

            $workspace->leads()->each(function (Lead $lead) use ($scoreLead, &$sourceRates, &$scored) {
                $sourceRates ??= $scoreLead->sourceRates($lead);
                $scoreLead->handle($lead, $sourceRates);
                $scored++;
            });

            Cache::tags("workspace:{$workspace->id}")->flush();
            $notifyWorkspace->handle($workspace->id, 'leads');
        });

        $this->info("Scored {$scored} leads.");

        return self::SUCCESS;
    }
}
