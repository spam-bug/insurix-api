<?php

namespace App\Models;

use App\Enums\PolicyEventType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An entry on a policy's timeline: recorded, a stage change, approved or declined, a
 * requirement added or received, or a renewal outcome.
 */
#[Fillable(['user_id', 'type', 'properties'])]
class PolicyEvent extends Model
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
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'type' => PolicyEventType::class,
            'properties' => 'array',
        ];
    }
}
