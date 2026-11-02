<?php

declare(strict_types=1);

namespace App\Actions\Automations;

use App\Enums\AutomationRunStatus;
use App\Models\AutomationRun;

class ResumeWaitingAutomationRuns
{
    public function __construct(private RunAutomation $runAutomation) {}

    /**
     * Carries on every run whose wait is over.
     *
     * @return int how many runs were due
     */
    public function handle(): int
    {
        $due = 0;

        AutomationRun::query()
            ->where('status', AutomationRunStatus::Waiting)
            ->where('resume_at', '<=', now())
            ->lazyById()
            ->each(function (AutomationRun $run) use (&$due) {
                $this->runAutomation->resume($run);
                $due++;
            });

        return $due;
    }
}
