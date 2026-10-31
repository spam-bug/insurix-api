<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Automations\RunAutomation;
use App\Enums\AutomationTrigger;
use App\Models\Automation;
use App\Models\Lead;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RunLeadAutomations implements ShouldQueue
{
    use Queueable;

    public function __construct(public Lead $lead, public AutomationTrigger $trigger) {}

    /** Runs each enabled automation for this trigger, in the order they were made. */
    public function handle(RunAutomation $runAutomation): void
    {
        $automations = Automation::query()
            ->with(['workspace', 'user'])
            ->where('workspace_id', $this->lead->workspace_id)
            ->where('enabled', true)
            ->where('trigger', $this->trigger)
            ->when($this->trigger === AutomationTrigger::LeadStageChanged, fn ($query) => $query->where(
                fn ($query) => $query->whereNull('trigger_stage')->orWhere('trigger_stage', $this->lead->status),
            ))
            ->oldest()
            ->get();

        foreach ($automations as $automation) {
            $runAutomation->handle($automation, $this->lead->refresh());
        }
    }
}
