<?php

namespace App\Models;

use App\Enums\PaymentFrequency;
use App\Enums\PolicyStatus;
use App\Enums\PolicyType;
use Carbon\CarbonImmutable;
use Database\Factories\PolicyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An insurance policy sold to a client, kept by hand from the insurer's documents.
 * Amounts are in pesos.
 */
#[Fillable([
    'user_id', 'client_id', 'product_id', 'insurer', 'product_name', 'policy_number', 'type', 'status', 'premium', 'coverage_amount', 'payment_frequency',
    'commission_rate', 'effective_date', 'renewal_date', 'commission_received_at', 'notes',
])]
class Policy extends Model
{
    /** @use HasFactory<PolicyFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * The catalog product this policy is for, when it was picked from the catalog.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * What the client pays in a policy year: the premium times the payments in a year.
     */
    public function annualPremium(): float
    {
        return round((float) $this->premium * $this->payment_frequency->paymentsPerYear(), 2);
    }

    /**
     * The agent's first-year commission on the policy: the annual premium times the rate.
     */
    public function commission(): float
    {
        return round($this->annualPremium() * (float) $this->commission_rate / 100, 2);
    }

    /**
     * The next premium due on or after $today, counting from the effective date. Null for
     * single-payment policies, ones without an effective date, and ones no longer in force.
     */
    public function nextPaymentDue(?CarbonImmutable $today = null): ?CarbonImmutable
    {
        $months = $this->payment_frequency->monthsBetweenPayments();

        if ($months === null || $this->effective_date === null || $this->status !== PolicyStatus::InForce) {
            return null;
        }

        $today ??= CarbonImmutable::today();
        $due = $this->effective_date;

        // Step from the effective date itself each time, so month-end dates don't drift.
        for ($period = 1; $due->lessThan($today); $period++) {
            $due = $this->effective_date->addMonthsNoOverflow($period * $months);
        }

        return $due;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'type' => PolicyType::class,
            'status' => PolicyStatus::class,
            'payment_frequency' => PaymentFrequency::class,
            'premium' => 'decimal:2',
            'coverage_amount' => 'decimal:2',
            'commission_rate' => 'decimal:2',
            'effective_date' => 'immutable_date',
            'renewal_date' => 'immutable_date',
            'commission_received_at' => 'immutable_date',
        ];
    }
}
