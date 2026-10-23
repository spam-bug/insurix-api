<?php

declare(strict_types=1);

namespace App\Actions\Policies;

use App\Enums\PolicyStatus;
use App\Models\Policy;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ListPoliciesNeedingAttention
{
    public function __construct(
        private FindRenewalAwaitingOutcome $findRenewalAwaitingOutcome,
        private FindNextRenewalDate $findNextRenewalDate,
        private FindNextPaymentDue $findNextPaymentDue,
    ) {}

    /** Premiums this many days out start showing. */
    public const PAYMENT_WINDOW_DAYS = 7;

    /** Applications pending longer than this are worth chasing with the insurer. */
    public const PENDING_DAYS = 14;

    /**
     * "Which policies need attention?": renewals coming up or past, premiums due soon, lapsed
     * policies to reinstate, and applications stuck in underwriting. Most urgent first.
     *
     * @return Collection<int, array{policy: Policy, reasons: array<int, array{code: string, label: string, date: string|null}>, due: CarbonImmutable}>
     */
    public function handle(User $agent): Collection
    {
        return Cache::tags("agent:{$agent->id}")->remember("policies:{$agent->id}:attention", now()->addMinutes(5), function () use ($agent) {
            $today = CarbonImmutable::now($agent->timezone)->startOfDay();

            return $agent->policies()
                ->with(['client:id,name,lead_id', 'renewals:id,policy_id,due_on'])
                ->withCount(['requirements as outstanding_requirements' => fn ($query) => $query->whereNull('received_on')])
                ->whereIn('status', [PolicyStatus::InForce, PolicyStatus::Lapsed, PolicyStatus::Pending])
                ->get()
                ->map(fn (Policy $policy) => $this->assess($policy, $today))
                ->filter()
                ->sortBy(fn (array $item) => $item['due']->timestamp)
                ->values();
        });
    }

    /**
     * @return array{policy: Policy, reasons: array<int, array{code: string, label: string, date: string|null}>, due: CarbonImmutable}|null
     */
    private function assess(Policy $policy, CarbonImmutable $today): ?array
    {
        $reasons = [];
        $dates = [];

        if ($policy->status === PolicyStatus::Lapsed) {
            $reasons[] = ['code' => 'lapsed', 'label' => 'Lapsed: help the client reinstate it', 'date' => null];
            $dates[] = $today;
        }

        // Counted in whole days in the agent's timezone, from the day it moved.
        $stageSince = ($policy->stage_changed_at ?? $policy->created_at)->toImmutable()->setTimezone($today->timezone)->startOfDay();
        if ($policy->status === PolicyStatus::Pending && $stageSince->lessThan($today->subDays(self::PENDING_DAYS))) {
            $days = (int) $stageSince->diffInDays($today);
            $stage = $policy->application_stage?->label() ?? 'Pending';
            $outstanding = (int) $policy->outstanding_requirements;
            $reasons[] = [
                'code' => 'pending',
                'label' => "{$stage} for {$days} days".($outstanding > 0 ? " with {$outstanding} ".($outstanding === 1 ? 'requirement' : 'requirements').' outstanding' : '').': check with the insurer',
                'date' => null,
            ];
            $dates[] = $today;
        }

        // Annual and custom terms wait for the agent to record each outcome; shorter terms roll over.
        $renewal = $policy->renewal_term?->tracksOutcomes()
            ? $this->findRenewalAwaitingOutcome->handle($policy, $today)
            : $this->findNextRenewalDate->handle($policy, $today);
        if ($policy->status === PolicyStatus::InForce && $renewal) {
            if ($renewal->lessThan($today)) {
                $reasons[] = ['code' => 'renewal_overdue', 'label' => 'Renewal date has passed: record how it went', 'date' => $renewal->toDateString()];
                $dates[] = $renewal;
            } elseif ($renewal->lessThanOrEqualTo($today->addDays($policy->renewal_term->attentionDays()))) {
                $reasons[] = ['code' => 'renewal_due', 'label' => 'Renewal coming up', 'date' => $renewal->toDateString()];
                $dates[] = $renewal;
            }
        }

        $nextPayment = $this->findNextPaymentDue->handle($policy, $today);
        if ($nextPayment && $nextPayment->lessThanOrEqualTo($today->addDays(self::PAYMENT_WINDOW_DAYS))) {
            $reasons[] = ['code' => 'payment_due', 'label' => 'Premium due soon', 'date' => $nextPayment->toDateString()];
            $dates[] = $nextPayment;
        }

        if ($reasons === []) {
            return null;
        }

        return ['policy' => $policy, 'reasons' => $reasons, 'due' => collect($dates)->min()];
    }
}
