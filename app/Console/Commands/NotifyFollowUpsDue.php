<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\FollowUps\NotifyFollowUpsDue as NotifyFollowUpsDueAction;
use App\Models\Workspace;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('follow-ups:notify-due')]
#[Description('Tell each workspace’s members how many follow-ups are due today')]
class NotifyFollowUpsDue extends Command
{
    public function handle(NotifyFollowUpsDueAction $notifyFollowUpsDue): int
    {
        $notified = 0;

        Workspace::query()->whereHas('followUps')->each(function (Workspace $workspace) use ($notifyFollowUpsDue, &$notified) {
            $notified += (int) $notifyFollowUpsDue->handle($workspace);
        });

        $this->info("Notified {$notified} workspaces.");

        return self::SUCCESS;
    }
}
