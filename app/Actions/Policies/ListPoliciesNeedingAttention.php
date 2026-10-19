<?php

namespace App\Actions\Policies;

use App\Enums\PolicyStatus;
use App\Models\Policy;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class ListPoliciesNeedingAttention
{
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
        $today = CarbonImmutable::now($agent->timezone)->startOfDay();

        return $agent->policies()
            ->with('client:id,name')
            ->whereIn('status', [PolicyStatus::InForce, PolicyStatus::Lapsed, PolicyStatus::Pending])
            ->get()
            ->map(fn (Policy $policy) => $this->assess($policy, $today))
            ->filter()
            ->sortBy(fn (array $item) => $item['due']->timestamp)
            ->values();
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

        if ($policy->status === PolicyStatus::Pending && $policy->created_at->lessThan($today->subDays(self::PENDING_DAYS))) {
            $days = (int) $policy->created_at->diffInDays($today);
            $reasons[] = ['code' => 'pending', 'label' => "Pending for {$days} days: check with the insurer", 'date' => null];
            $dates[] = $today;
        }

        $renewal = $policy->nextRenewalDate($today);
        if ($policy->status === PolicyStatus::InForce && $renewal) {
            if ($renewal->lessThan($today)) {
                $reasons[] = ['code' => 'renewal_overdue', 'label' => 'Renewal date has passed', 'date' => $renewal->toDateString()];
                $dates[] = $renewal;
            } elseif ($renewal->lessThanOrEqualTo($today->addDays($policy->renewal_term->attentionDays()))) {
                $reasons[] = ['code' => 'renewal_due', 'label' => 'Renewal coming up', 'date' => $renewal->toDateString()];
                $dates[] = $renewal;
            }
        }

        $nextPayment = $policy->nextPaymentDue($today);
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
