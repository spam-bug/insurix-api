<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PolicyPaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One premium the client paid, kept by hand. It carries the commission the agent expects from
 * it (their rate at the time) and, once their statement shows it, what they actually received.
 * Amounts are in pesos.
 */
#[Fillable(['workspace_id', 'due_on', 'paid_on', 'amount', 'commission_expected', 'commission_received', 'commission_received_on', 'notes'])]
class PolicyPayment extends Model
{
    /** @use HasFactory<PolicyPaymentFactory> */
    use HasFactory, HasUuids;

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
            'paid_on' => 'immutable_date',
            'amount' => 'decimal:2',
            'commission_expected' => 'decimal:2',
            'commission_received' => 'decimal:2',
            'commission_received_on' => 'immutable_date',
        ];
    }
}
