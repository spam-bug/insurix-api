<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AutomationRunStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One time an automation ran for a lead. A run paused at a wait keeps the steps still to come
 * and when to carry on; `automations:resume` picks it up then.
 */
#[Fillable(['automation_id', 'lead_id', 'status', 'pending_actions', 'resume_at', 'created_at'])]
class AutomationRun extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return BelongsTo<Automation, $this>
     */
    public function automation(): BelongsTo
    {
        return $this->belongsTo(Automation::class);
    }

    /**
     * @return BelongsTo<Lead, $this>
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AutomationRunStatus::class,
            'pending_actions' => 'array',
            'resume_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
        ];
    }
}
