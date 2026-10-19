<?php

namespace App\Http\Resources\Api\V1;

use App\Models\ApplicationRequirement;
use App\Models\Policy;
use App\Models\PolicyEvent;
use App\Models\PolicyRenewal;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Policy
 */
class PolicyResource extends JsonResource
{
    /** What the policy's own page shows: the application checklist, renewals and timeline. */
    public const DETAIL_RELATIONS = [
        'client:id,name',
        'requirements.document:id,name',
        'renewals',
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
            'client' => $this->whenLoaded('client', fn () => ['id' => $this->client->id, 'name' => $this->client->name]),
            'product_id' => $this->product_id,
            'product_name' => $this->product_name,
            'policy_number' => $this->policy_number,
            'type' => ['value' => $this->type->value, 'label' => $this->type->label()],
            'status' => ['value' => $this->status->value, 'label' => $this->status->label()],
            'application_stage' => $this->application_stage ? ['value' => $this->application_stage->value, 'label' => $this->application_stage->label()] : null,
            'stage_changed_at' => $this->stage_changed_at?->toIso8601String(),
            'premium' => (float) $this->premium,
            'coverage_amount' => $this->coverage_amount === null ? null : (float) $this->coverage_amount,
            'payment_frequency' => ['value' => $this->payment_frequency->value, 'label' => $this->payment_frequency->label()],
            'annual_premium' => $this->annualPremium(),
            'commission_rate' => (float) $this->commission_rate,
            'commission' => $this->commission(),
            'effective_date' => $this->effective_date?->toDateString(),
            'renewal_term' => $this->renewal_term ? ['value' => $this->renewal_term->value, 'label' => $this->renewal_term->label()] : null,
            // The next renewal: worked out for set terms, the fixed date for a custom one.
            'renewal_date' => $this->nextRenewalDate()?->toDateString(),
            'tracks_renewal_outcomes' => $this->tracksRenewalOutcomes(),
            'next_payment_due' => $this->nextPaymentDue()?->toDateString(),
            'commission_received_at' => $this->commission_received_at?->toDateString(),
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
            'renewal_awaiting_outcome' => $this->whenLoaded('renewals', fn () => $this->renewalAwaitingOutcome(
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
