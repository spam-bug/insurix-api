<?php

declare(strict_types=1);

namespace App\Models;

use App\Builders\NoteBuilder;
use Database\Factories\NoteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Query\Builder;

#[Fillable(['workspace_id', 'user_id', 'lead_id', 'client_id', 'policy_id', 'lead_activity_id', 'body', 'pinned_at'])]
class Note extends Model
{
    /** @use HasFactory<NoteFactory> */
    use HasFactory, HasUuids;

    /**
     * @param  Builder  $query
     */
    public function newEloquentBuilder($query): NoteBuilder
    {
        return new NoteBuilder($query);
    }

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * The author.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Lead, $this>
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return BelongsTo<Policy, $this>
     */
    public function policy(): BelongsTo
    {
        return $this->belongsTo(Policy::class);
    }

    /**
     * The note's entry in its lead's timeline.
     *
     * @return BelongsTo<LeadActivity, $this>
     */
    public function leadActivity(): BelongsTo
    {
        return $this->belongsTo(LeadActivity::class);
    }

    /**
     * @return MorphMany<Mention, $this>
     */
    public function mentions(): MorphMany
    {
        return $this->morphMany(Mention::class, 'mentionable');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pinned_at' => 'immutable_datetime',
        ];
    }
}
