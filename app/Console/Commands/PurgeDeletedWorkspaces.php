<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Workspaces\PurgeWorkspace;
use App\Models\Workspace;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('workspaces:purge')]
#[Description('Remove workspaces deleted longer ago than the grace period, with their files')]
class PurgeDeletedWorkspaces extends Command
{
    public function handle(PurgeWorkspace $purgeWorkspace): int
    {
        $purged = 0;

        Workspace::onlyTrashed()
            ->where('deleted_at', '<=', now()->subDays(Workspace::DELETION_GRACE_DAYS))
            ->each(function (Workspace $workspace) use ($purgeWorkspace, &$purged) {
                $purgeWorkspace->handle($workspace);
                $purged++;
            });

        $this->info("Purged {$purged} deleted workspaces.");

        return self::SUCCESS;
    }
}
