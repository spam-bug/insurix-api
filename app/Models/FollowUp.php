<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FollowUpType;
use Database\Factories\FollowUpFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Something the agent means to do for a lead or a client by a due time. It keeps the lead it
 * started on after promotion (so it stays in the lead's history) and gains the client.
 */
#[Fillable(['workspace_id', 'lead_id', 'client_id', 'policy_id', 'reminder_key', 'type', 'title', 'notes', 'due_at', 'completed_at'])]
class FollowUp extends Model
{
    /** @use HasFactory<FollowUpFactory> */
    use HasFactory, HasUuids;

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
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
     * The policy a renewal or premium reminder is for.
     *
     * @return BelongsTo<Policy, $this>
     */
    public function policy(): BelongsTo
    {
        return $this->belongsTo(Policy::class);
    }

    public function isOverdue(): bool
    {
        return $this->completed_at === null && $this->due_at->isPast();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'type' => FollowUpType::class,
            'due_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
        ];
    }
}
