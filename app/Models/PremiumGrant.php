<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One time Premium was given to a workspace, through a link or directly; the history of beta testers and comps. */
#[Fillable(['premium_link_id', 'workspace_id', 'user_id', 'days', 'plan_ends_at', 'note'])]
class PremiumGrant extends Model
{
    use HasUuids;

    /**
     * @return BelongsTo<PremiumLink, $this>
     */
    public function premiumLink(): BelongsTo
    {
        return $this->belongsTo(PremiumLink::class);
    }

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Who claimed it, or whose workspace was given it.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'days' => 'integer',
            'plan_ends_at' => 'datetime',
        ];
    }
}
