<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ApplicationStage;
use App\Enums\PaymentFrequency;
use App\Enums\PolicyStatus;
use App\Enums\PolicyType;
use App\Enums\RenewalTerm;
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

    /**
     * Only a pending application has a stage. One that becomes pending starts at Submitted,
     * counting from when it was recorded; any other status clears the stage. The annual premium
     * and first-year commission are stored so reports can sum them in SQL.
     */
    protected static function booted(): void
    {
        static::saving(function (Policy $policy) {
            $policy->annual_premium = round((float) $policy->premium * $policy->payment_frequency->paymentsPerYear(), 2);
            $policy->commission = round((float) $policy->annual_premium * (float) $policy->commission_rate / 100, 2);

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
            'annual_premium' => 'decimal:2',
            'commission' => 'decimal:2',
            'effective_date' => 'immutable_date',
            'renewal_term' => RenewalTerm::class,
            'renewal_date' => 'immutable_date',
            'commission_received_at' => 'immutable_date',
        ];
    }
}
