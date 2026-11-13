<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Actions\Policies\FindNextPaymentDue;
use App\Actions\Policies\FindNextRenewalDate;
use App\Actions\Policies\FindRenewalAwaitingOutcome;
use App\Models\ApplicationRequirement;
use App\Models\Policy;
use App\Models\PolicyEvent;
use App\Models\PolicyPayment;
use App\Models\PolicyRenewal;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Policy
 */
class PolicyResource extends JsonResource
{
    /** What the policy's own page shows: the application checklist, renewals, payments and timeline. */
    public const DETAIL_RELATIONS = [
        'client:id,name,lead_id',
        'requirements.document:id,name',
        'renewals',
        'payments',
        'events.user:id,name',
    ];

    /**
     * Transform the resource into an array. Amounts are pesos as numbers; dates are YYYY-MM-DD.
     * The checklist, renewals and timeline are only there when loaded (DETAIL_RELATIONS).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client' => $this->whenLoaded('client', fn () => ['id' => $this->client->id, 'name' => $this->client->name, 'lead_id' => $this->client->lead_id]),
            'product_id' => $this->product_id,
            'product_name' => $this->product_name,
            'insurer' => $this->insurer,
            'policy_number' => $this->policy_number,
            'type' => ['value' => $this->type->value, 'label' => $this->type->label()],
            'status' => ['value' => $this->status->value, 'label' => $this->status->label()],
            'application_stage' => $this->application_stage ? ['value' => $this->application_stage->value, 'label' => $this->application_stage->label()] : null,
            'stage_changed_at' => $this->stage_changed_at?->toIso8601String(),
            'premium' => (float) $this->premium,
            'coverage_amount' => $this->coverage_amount === null ? null : (float) $this->coverage_amount,
            'payment_frequency' => ['value' => $this->payment_frequency->value, 'label' => $this->payment_frequency->label()],
            'annual_premium' => (float) $this->annual_premium,
            'commission_rate' => (float) $this->commission_rate,
            'renewal_commission_rate' => (float) $this->renewal_commission_rate,
            'commission' => (float) $this->commission,
            'effective_date' => $this->effective_date?->toDateString(),
            'renewal_term' => $this->renewal_term ? ['value' => $this->renewal_term->value, 'label' => $this->renewal_term->label()] : null,
            // The next renewal: worked out for set terms, the fixed date for a custom one.
            'renewal_date' => app(FindNextRenewalDate::class)->handle($this->resource)?->toDateString(),
            'tracks_renewal_outcomes' => $this->renewal_term?->tracksOutcomes() ?? false,
            // The earliest premium not recorded as paid; in the past when it's overdue.
            'next_payment_due' => app(FindNextPaymentDue::class)->handle($this->resource)?->toDateString(),
            'payment_grace_days' => FindNextPaymentDue::GRACE_DAYS,
            'commission_received_total' => $this->whenHas('commission_received_total', fn () => round((float) $this->commission_received_total, 2)),
            'notes' => $this->notes,
            'requirements' => $this->whenLoaded('requirements', fn () => $this->requirements
                ->sortBy([['received_on', 'asc'], ['id', 'asc']])
                ->values()
                ->map(fn (ApplicationRequirement $requirement) => [
                    'id' => $requirement->id,
                    'title' => $requirement->title,
                    'received_on' => $requirement->received_on?->toDateString(),
                    'document' => $requirement->document ? ['id' => $requirement->document->id, 'name' => $requirement->document->name] : null,
                ])),
            'payments' => $this->whenLoaded('payments', fn () => $this->payments
                ->sortByDesc(fn (PolicyPayment $payment) => $payment->due_on->timestamp)
                ->values()
                ->map(fn (PolicyPayment $payment) => [
                    'id' => $payment->id,
                    'due_on' => $payment->due_on->toDateString(),
                    'paid_on' => $payment->paid_on->toDateString(),
                    'amount' => (float) $payment->amount,
                    'commission_expected' => (float) $payment->commission_expected,
                    'commission_received' => $payment->commission_received === null ? null : (float) $payment->commission_received,
                    'commission_received_on' => $payment->commission_received_on?->toDateString(),
                    'notes' => $payment->notes,
                ])),
            'renewal_awaiting_outcome' => $this->whenLoaded('renewals', fn () => app(FindRenewalAwaitingOutcome::class)->handle(
                $this->resource,
                CarbonImmutable::now($request->user()?->timezone ?? config('app.timezone'))->startOfDay()
            )?->toDateString()),
            'renewals' => $this->whenLoaded('renewals', fn () => $this->renewals
                ->sortByDesc(fn (PolicyRenewal $renewal) => $renewal->due_on->timestamp)
                ->values()
                ->map(fn (PolicyRenewal $renewal) => [
                    'id' => $renewal->id,
                    'due_on' => $renewal->due_on->toDateString(),
                    'outcome' => ['value' => $renewal->outcome->value, 'label' => $renewal->outcome->label()],
                    'notes' => $renewal->notes,
                    'recorded_at' => $renewal->created_at?->toIso8601String(),
                ])),
            'events' => $this->whenLoaded('events', fn () => $this->events
                ->sortByDesc(fn (PolicyEvent $event) => [$event->created_at?->timestamp, $event->id])
                ->values()
                ->map(fn (PolicyEvent $event) => [
                    'id' => $event->id,
                    'type' => $event->type->value,
                    'properties' => $event->properties ?? (object) [],
                    'actor' => $event->user ? ['id' => $event->user->id, 'name' => $event->user->name] : null,
                    'created_at' => $event->created_at?->toIso8601String(),
                ])),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
