<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Notifications\DatabaseNotification;

#[Signature('notifications:prune')]
#[Description('Delete bell notifications older than 90 days')]
class PruneNotifications extends Command
{
    /** Notifications are kept this many days, read or not. */
    public const KEEP_DAYS = 90;

    public function handle(): int
    {
        $deleted = DatabaseNotification::query()->where('created_at', '<', now()->subDays(self::KEEP_DAYS))->delete();

        $this->info("Deleted {$deleted} notifications.");

        return self::SUCCESS;
    }
}
