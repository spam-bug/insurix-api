<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Automations\RunIdleLeadAutomations;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('automations:run-idle')]
#[Description('Run "no activity" automations for leads that have gone quiet')]
class RunIdleAutomations extends Command
{
    public function handle(RunIdleLeadAutomations $runIdleLeadAutomations): int
    {
        $this->info("Ran {$runIdleLeadAutomations->handle()} automations for quiet leads.");

        return self::SUCCESS;
    }
}
