<?php

declare(strict_types=1);

namespace App\Actions\Automations;

use App\Enums\AutomationTrigger;
use App\Enums\Feature;
use App\Enums\LeadStatus;
use App\Models\Automation;
use Illuminate\Support\Facades\DB;
use Laravel\Pennant\Feature as Pennant;

class RunIdleLeadAutomations
{
    public function __construct(private RunAutomation $runAutomation) {}

    /**
     * For each "no activity" automation, the open leads that have been quiet for its number of
     * days: nothing in their timeline (or since they were added) for that long. Each quiet spell
     * runs the automation once; any new activity starts a new spell.
     *
     * @return int how many runs happened
     */
    public function handle(): int
    {
        $runs = 0;

        $automations = Automation::query()
            ->with(['workspace', 'user'])
            ->where('enabled', true)
            ->where('trigger', AutomationTrigger::LeadIdle)
            ->whereNotNull('idle_days')
            ->get()
            ->filter(fn (Automation $automation) => Pennant::for($automation->workspace)->active(Feature::Automations));

        foreach ($automations as $automation) {
            $lastActivity = DB::raw('coalesce((select max(created_at) from lead_activities where lead_activities.lead_id = leads.id), leads.created_at)');

            $leads = $automation->workspace->leads()
                ->whereIn('status', LeadStatus::open())
                ->where($lastActivity, '<=', now()->subDays($automation->idle_days))
                ->whereNotExists(fn ($query) => $query->selectRaw('1')
                    ->from('automation_runs')
                    ->where('automation_runs.automation_id', $automation->id)
                    ->whereColumn('automation_runs.lead_id', 'leads.id')
                    ->where('automation_runs.created_at', '>=', $lastActivity))
                ->get();

            foreach ($leads as $lead) {
                $runs += (int) $this->runAutomation->handle($automation, $lead);
            }
        }

        return $runs;
    }
}
