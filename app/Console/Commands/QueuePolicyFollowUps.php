<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\FollowUps\QueuePolicyFollowUps as QueuePolicyFollowUpsAction;
use App\Models\User;
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

        User::query()->whereHas('policies')->each(function (User $agent) use ($queuePolicyFollowUps, &$created) {
            $created += $queuePolicyFollowUps->handle($agent);
        });

        $this->info("Created {$created} policy follow-ups.");

        return self::SUCCESS;
    }
}
