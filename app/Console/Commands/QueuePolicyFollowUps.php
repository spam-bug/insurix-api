<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\FollowUps\QueuePolicyFollowUps as QueuePolicyFollowUpsAction;
use App\Models\Workspace;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('policies:queue-follow-ups')]
#[Description('Create follow-ups for renewals and premiums coming due')]
class QueuePolicyFollowUps extends Command
{
    public function handle(QueuePolicyFollowUpsAction $queuePolicyFollowUps): int
    {
        $created = 0;

        Workspace::query()->whereHas('policies')->each(function (Workspace $workspace) use ($queuePolicyFollowUps, &$created) {
            $created += $queuePolicyFollowUps->handle($workspace);
        });

        $this->info("Created {$created} policy follow-ups.");

        return self::SUCCESS;
    }
}
