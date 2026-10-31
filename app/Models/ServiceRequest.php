<?php

declare(strict_types=1);

namespace App\Models;

use App\Builders\ServiceRequestBuilder;
use App\Enums\ServiceRequestStatus;
use App\Enums\ServiceRequestType;
use Database\Factories\ServiceRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Something a client asked for: an inquiry, a policy change or a claim the agent follows through. */
#[Fillable(['workspace_id', 'client_id', 'policy_id', 'user_id', 'type', 'status', 'title', 'details', 'due_on', 'claim_amount', 'insurer_reference', 'resolved_at'])]
class ServiceRequest extends Model
{
    /** @use HasFactory<ServiceRequestFactory> */
    use HasFactory, HasUuids;

    /**
     * @param  Builder  $query
     */
    public function newEloquentBuilder($query): ServiceRequestBuilder
    {
        return new ServiceRequestBuilder($query);
    }

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
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
     * Who logged it.
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
            'type' => ServiceRequestType::class,
            'status' => ServiceRequestStatus::class,
            'due_on' => 'immutable_date',
            'claim_amount' => 'decimal:2',
            'resolved_at' => 'immutable_datetime',
        ];
    }
}
