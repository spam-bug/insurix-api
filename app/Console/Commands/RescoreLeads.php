<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Leads\ScoreLead;
use App\Actions\Notifications\AnnounceWorkspaceChange;
use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
use App\Models\Lead;
use App\Models\Workspace;
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
    public function handle(ScoreLead $scoreLead, AnnounceWorkspaceChange $announceWorkspaceChange): int
    {
        $scored = 0;

        Workspace::query()->whereHas('leads')->each(function (Workspace $workspace) use ($scoreLead, $announceWorkspaceChange, &$scored) {
            $sourceRates = null;

            $rescored = 0;

            $workspace->leads()->each(function (Lead $lead) use ($scoreLead, &$sourceRates, &$rescored) {
                $sourceRates ??= $scoreLead->sourceRates($lead);
                $scoreLead->handle($lead, $sourceRates);
                $rescored++;
            });

            $scored += $rescored;
            $announceWorkspaceChange->handle($workspace->id, new WorkspaceChangeData(ChangeTopic::Leads, ChangeAction::Updated, count: $rescored));
        });

        $this->info("Scored {$scored} leads.");

        return self::SUCCESS;
    }
}
