<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ApplicationStage;
use App\Enums\PaymentFrequency;
use App\Enums\PolicyStatus;
use App\Enums\PolicyType;
use App\Enums\RenewalTerm;
use Carbon\CarbonImmutable;
use Database\Factories\PolicyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An insurance policy sold to a client, kept by hand from the insurer's documents.
 * Amounts are in pesos.
 */
#[Fillable([
    'user_id', 'client_id', 'product_id', 'product_name', 'policy_number', 'type', 'status', 'application_stage', 'stage_changed_at',
    'premium', 'coverage_amount', 'payment_frequency', 'commission_rate', 'effective_date', 'renewal_term', 'renewal_date',
    'commission_received_at', 'notes',
])]
class Policy extends Model
{
    /** @use HasFactory<PolicyFactory> */
    use HasFactory, HasUuids;

    /** Renewal dates this far back still wait for an outcome; older ones are let go. */
    public const RENEWAL_LOOKBACK_DAYS = 90;

    /**
     * Only a pending application has a stage. One that becomes pending starts at Submitted,
     * counting from when it was recorded; any other status clears the stage.
     */
    protected static function booted(): void
    {
        static::saving(function (Policy $policy) {
            if ($policy->status !== PolicyStatus::Pending) {
                $policy->application_stage = null;
                $policy->stage_changed_at = null;
            } elseif ($policy->application_stage === null) {
                $policy->application_stage = ApplicationStage::Submitted;
                $policy->stage_changed_at = $policy->created_at ?? now();
            }
        });
    }

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
     * Renewal and premium reminders created for this policy.
     *
     * @return HasMany<FollowUp, $this>
     */
    public function followUps(): HasMany
    {
        return $this->hasMany(FollowUp::class);
    }

    /**
     * Files kept for this policy, such as its contract.
     *
     * @return HasMany<Document, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /**
     * What the insurer still needs before deciding on the application.
     *
     * @return HasMany<ApplicationRequirement, $this>
     */
    public function requirements(): HasMany
    {
        return $this->hasMany(ApplicationRequirement::class);
    }

    /**
     * The outcome recorded for each renewal date.
     *
     * @return HasMany<PolicyRenewal, $this>
     */
    public function renewals(): HasMany
    {
        return $this->hasMany(PolicyRenewal::class);
    }

    /**
     * @return HasMany<PolicyEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(PolicyEvent::class);
    }

    /**
     * Whether the agent records an outcome for each renewal. Annual and custom-date terms
     * only: monthly and quarterly ones roll over like payments and aren't tracked one by one.
     */
    public function tracksRenewalOutcomes(): bool
    {
        return in_array($this->renewal_term, [RenewalTerm::Annual, RenewalTerm::Custom], true);
    }

    /**
     * The earliest renewal date that's due for an outcome and doesn't have one yet: from
     * RENEWAL_LOOKBACK_DAYS ago up to the term's attention window ahead. Needs the renewals
     * relation; loads it when missing.
     */
    public function renewalAwaitingOutcome(?CarbonImmutable $today = null): ?CarbonImmutable
    {
        if ($this->status !== PolicyStatus::InForce || ! $this->tracksRenewalOutcomes()) {
            return null;
        }

        $today ??= CarbonImmutable::today();
        $recorded = $this->renewals->map(fn (PolicyRenewal $renewal) => $renewal->due_on->toDateString())->all();

        foreach ($this->renewalDatesBetween($today->subDays(self::RENEWAL_LOOKBACK_DAYS), $today->addDays($this->renewal_term->attentionDays())) as $date) {
            if (! in_array($date->toDateString(), $recorded, true)) {
                return $date;
            }
        }

        return null;
    }

    /**
     * Every renewal date from $from to $to, oldest first.
     *
     * @return array<int, CarbonImmutable>
     */
    public function renewalDatesBetween(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $months = $this->renewal_term?->months();

        if ($months === null || $this->effective_date === null) {
            $date = $this->renewal_term === RenewalTerm::Custom ? $this->renewal_date : null;

            return $date && $date->betweenIncluded($from, $to) ? [$date] : [];
        }

        $dates = [];
        for ($period = 1; ($date = $this->effective_date->addMonthsNoOverflow($period * $months))->lessThanOrEqualTo($to); $period++) {
            if ($date->greaterThanOrEqualTo($from)) {
                $dates[] = $date;
            }
        }

        return $dates;
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

        return $this->nextFromEffectiveDate($months, $today ?? CarbonImmutable::today(), includeStart: true);
    }

    /**
     * The next renewal, when it falls between $today and $days from now. Monthly and quarterly
     * terms use their own shorter window instead, so they don't count as coming up all the time.
     */
    public function renewalWithin(CarbonImmutable $today, int $days): ?CarbonImmutable
    {
        $renewal = $this->nextRenewalDate($today);
        if ($renewal === null || $renewal->lessThan($today)) {
            return null;
        }

        $months = $this->renewal_term->months();
        $window = $months !== null && $months < 12 ? min($days, $this->renewal_term->attentionDays()) : $days;

        return $renewal->lessThanOrEqualTo($today->addDays($window)) ? $renewal : null;
    }

    /**
     * The next renewal on or after $today. Set terms count from the effective date (the first
     * renewal is one term after it); a custom term is its fixed date, even once it has passed.
     */
    public function nextRenewalDate(?CarbonImmutable $today = null): ?CarbonImmutable
    {
        $months = $this->renewal_term?->months();

        if ($months === null || $this->effective_date === null) {
            return $this->renewal_term === RenewalTerm::Custom ? $this->renewal_date : null;
        }

        return $this->nextFromEffectiveDate($months, $today ?? CarbonImmutable::today(), includeStart: false);
    }

    /**
     * The first date on or after $today that is a whole number of $months after the effective
     * date. Steps from the effective date itself each time, so month-end dates don't drift.
     */
    private function nextFromEffectiveDate(int $months, CarbonImmutable $today, bool $includeStart): CarbonImmutable
    {
        $period = $includeStart ? 0 : 1;
        $date = $this->effective_date->addMonthsNoOverflow($period * $months);

        while ($date->lessThan($today)) {
            $date = $this->effective_date->addMonthsNoOverflow(++$period * $months);
        }

        return $date;
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
            'application_stage' => ApplicationStage::class,
            'stage_changed_at' => 'immutable_datetime',
            'payment_frequency' => PaymentFrequency::class,
            'premium' => 'decimal:2',
            'coverage_amount' => 'decimal:2',
            'commission_rate' => 'decimal:2',
            'effective_date' => 'immutable_date',
            'renewal_term' => RenewalTerm::class,
            'renewal_date' => 'immutable_date',
            'commission_received_at' => 'immutable_date',
        ];
    }
}
