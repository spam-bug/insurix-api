<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Automation;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Automation
 */
class AutomationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'trigger' => ['value' => $this->trigger->value, 'label' => $this->trigger->label()],
            'trigger_stage' => $this->trigger_stage?->value,
            'idle_days' => $this->idle_days,
            'match' => $this->match->value,
            'conditions' => $this->conditions,
            'actions' => $this->actions,
            'enabled' => $this->enabled,
            'runs_count' => $this->whenCounted('runs'),
            'last_run_at' => $this->whenHas('runs_max_created_at', fn () => $this->runs_max_created_at ? CarbonImmutable::parse($this->runs_max_created_at)->toIso8601String() : null),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
