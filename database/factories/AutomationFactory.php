<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AutomationMatch;
use App\Enums\AutomationTrigger;
use App\Models\Automation;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Automation>
 */
class AutomationFactory extends Factory
{
    /**
     * A rule that adds a call follow-up for every new lead.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'name' => 'Call new leads',
            'trigger' => AutomationTrigger::LeadCreated,
            'match' => AutomationMatch::All,
            'conditions' => [],
            'actions' => [['type' => 'add_follow_up', 'follow_up_type' => 'call', 'title' => 'Call the new lead', 'due_in_hours' => 1]],
            'enabled' => true,
        ];
    }
}
