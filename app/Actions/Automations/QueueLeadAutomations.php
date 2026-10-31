<?php

declare(strict_types=1);

namespace App\Actions\Automations;

use App\Enums\AutomationTrigger;
use App\Enums\Feature;
use App\Enums\LeadActivityType;
use App\Jobs\RunLeadAutomations;
use App\Models\Automation;
use App\Models\Lead;
use App\Models\LeadActivity;
use Laravel\Pennant\Feature as Pennant;

class QueueLeadAutomations
{
    /**
     * A new lead or a stage change sets off the workspace's matching automations, in the
     * background once the change is saved. Changes an automation made itself are skipped, so
     * automations can't set each other off in a loop.
     */
    public function handle(Lead $lead, LeadActivity $activity): void
    {
        $trigger = match ($activity->type) {
            LeadActivityType::Created => AutomationTrigger::LeadCreated,
            LeadActivityType::StatusChanged => AutomationTrigger::LeadStageChanged,
            default => null,
        };

        if (! $trigger || isset($activity->properties['automation_id'])) {
            return;
        }

        $hasAutomations = Automation::query()
            ->where('workspace_id', $lead->workspace_id)
            ->where('enabled', true)
            ->where('trigger', $trigger)
            ->exists();

        if (! $hasAutomations || Pennant::for($lead->loadMissing('workspace')->workspace)->inactive(Feature::Automations)) {
            return;
        }

        RunLeadAutomations::dispatch($lead, $trigger)->afterCommit();
    }
}
