<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RenewalOutcome;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * What the agent recorded for one of a policy's renewal dates.
 */
#[Fillable(['workspace_id', 'due_on', 'outcome', 'notes'])]
class PolicyRenewal extends Model
{
    use HasUuids;

    /**
     * @return BelongsTo<Policy, $this>
     */
    public function policy(): BelongsTo
    {
        return $this->belongsTo(Policy::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'due_on' => 'immutable_date',
            'outcome' => RenewalOutcome::class,
        ];
    }
}
