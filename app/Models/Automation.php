<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AutomationMatch;
use App\Enums\AutomationTrigger;
use App\Enums\LeadStatus;
use Database\Factories\AutomationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A rule for leads: when the trigger happens and the conditions hold, the actions run.
 * Conditions are `{field, operator, value}` items; actions are `{type, ...options}` items.
 */
#[Fillable(['workspace_id', 'user_id', 'name', 'trigger', 'trigger_stage', 'idle_days', 'match', 'conditions', 'actions', 'enabled'])]
class Automation extends Model
{
    /** @use HasFactory<AutomationFactory> */
    use HasFactory, HasUuids;

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Who made it; their name goes on the notes it adds.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<AutomationRun, $this>
     */
    public function runs(): HasMany
    {
        return $this->hasMany(AutomationRun::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'trigger' => AutomationTrigger::class,
            'trigger_stage' => LeadStatus::class,
            'match' => AutomationMatch::class,
            'conditions' => 'array',
            'actions' => 'array',
            'enabled' => 'boolean',
        ];
    }
}
