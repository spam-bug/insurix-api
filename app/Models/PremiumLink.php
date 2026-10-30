<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PremiumLinkState;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A shareable link that gives Premium to the workspace of whoever claims it. No days means forever. */
#[Fillable(['token', 'days', 'max_claims', 'email', 'note', 'valid_until', 'created_by'])]
class PremiumLink extends Model
{
    use HasUuids;

    /** Needs `grants_count` loaded. */
    public function state(): PremiumLinkState
    {
        return match (true) {
            $this->valid_until?->isPast() ?? false => PremiumLinkState::Expired,
            $this->grants_count >= $this->max_claims => PremiumLinkState::UsedUp,
            default => PremiumLinkState::Available,
        };
    }

    /**
     * @return HasMany<PremiumGrant, $this>
     */
    public function grants(): HasMany
    {
        return $this->hasMany(PremiumGrant::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'days' => 'integer',
            'max_claims' => 'integer',
            'valid_until' => 'datetime',
        ];
    }
}
