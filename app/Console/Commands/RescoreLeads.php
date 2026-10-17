<?php

namespace App\Console\Commands;

use App\Actions\Leads\ScoreLead;
use App\Actions\Notifications\NotifyAgent;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Scores change with time as well as activity (days since contact, follow-ups falling due),
 * so every lead is rescored daily.
 */
#[Signature('leads:rescore')]
#[Description('Recalculate every lead’s likelihood-to-convert score')]
class RescoreLeads extends Command
{
    public function handle(ScoreLead $scoreLead, NotifyAgent $notifyAgent): int
    {
        $scored = 0;

        User::query()->whereHas('leads')->each(function (User $agent) use ($scoreLead, $notifyAgent, &$scored) {
            $sourceRates = null;

            $agent->leads()->with('user')->each(function (Lead $lead) use ($scoreLead, &$sourceRates, &$scored) {
                $sourceRates ??= $scoreLead->sourceRates($lead);
                $scoreLead->handle($lead, $sourceRates);
                $scored++;
            });

            $notifyAgent->handle($agent->id, 'leads');
        });

        $this->info("Scored {$scored} leads.");

        return self::SUCCESS;
    }
}
