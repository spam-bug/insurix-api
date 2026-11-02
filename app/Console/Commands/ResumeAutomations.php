<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Automations\ResumeWaitingAutomationRuns;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('automations:resume')]
#[Description('Carry on automation runs whose wait is over')]
class ResumeAutomations extends Command
{
    public function handle(ResumeWaitingAutomationRuns $resumeWaitingAutomationRuns): int
    {
        $this->info("Resumed {$resumeWaitingAutomationRuns->handle()} automation runs.");

        return self::SUCCESS;
    }
}
