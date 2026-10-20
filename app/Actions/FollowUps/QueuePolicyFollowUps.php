<?php

declare(strict_types=1);

namespace App\Actions\FollowUps;

use App\Actions\Notifications\NotifyAgent;
use App\Actions\Policies\ListPoliciesNeedingAttention;
use App\DataTransferObjects\FollowUps\FollowUpData;
use App\Enums\FollowUpType;
use App\Enums\PolicyStatus;
use App\Models\Policy;
use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * Puts renewals and premiums coming due on the agent's follow-ups, so their Today list covers
 * policies as well as people. Each reminder is created once per renewal or premium date.
 */
class QueuePolicyFollowUps
{
    /** Reminders are due at this hour, in the agent's timezone. */
    private const DUE_HOUR = 9;

    /** A renewal reminder comes up this many days before the renewal date, or less for short terms. */
    private const RENEWAL_LEAD_DAYS = 14;

    /** A premium reminder comes up this many days before the premium is due. */
    private const PREMIUM_LEAD_DAYS = 2;

    public function __construct(
        private CreateFollowUp $createFollowUp,
        private NotifyAgent $notifyAgent,
    ) {}

    /**
     * @return int how many follow-ups were created
     */
    public function handle(User $agent): int
    {
        $today = CarbonImmutable::now($agent->timezone)->startOfDay();
        $created = 0;

        $policies = $agent->policies()
            ->with('client.lead', 'followUps:id,policy_id,reminder_key', 'renewals:id,policy_id,due_on')
            ->where('status', PolicyStatus::InForce)
            ->get();

        foreach ($policies as $policy) {
            foreach ($this->remindersFor($policy, $today) as [$key, $type, $title, $dueOn]) {
                if ($policy->client->lead === null || $policy->followUps->contains('reminder_key', $key)) {
                    continue;
                }

                // Policy dates are plain dates, so read them in the agent's timezone. A reminder
                // whose hour has already passed today is due right away rather than overdue.
                $dueAt = CarbonImmutable::parse($dueOn->toDateString(), $agent->timezone)->setTime(self::DUE_HOUR, 0);
                $dueAt = max($dueAt, CarbonImmutable::now($agent->timezone))->utc();

                $this->createFollowUp->handle(
                    $policy->client->lead,
                    new FollowUpData(type: $type, title: $title, dueAt: $dueAt, notes: null),
                    ['policy_id' => $policy->id, 'reminder_key' => $key],
                );
                $created++;
            }
        }

        if ($created > 0) {
            $this->notifyAgent->handle($agent->id, 'follow-ups');
        }

        return $created;
    }

    /**
     * @return array<int, array{0: string, 1: FollowUpType, 2: string, 3: CarbonImmutable}>
     */
    private function remindersFor(Policy $policy, CarbonImmutable $today): array
    {
        $reminders = [];
        $renewal = $policy->nextRenewalDate($today);
        $alreadyRecorded = $renewal && $policy->renewals->contains(fn ($recorded) => $recorded->due_on->equalTo($renewal));

        if ($renewal && ! $alreadyRecorded && $renewal->greaterThanOrEqualTo($today) && $renewal->lessThanOrEqualTo($today->addDays($policy->renewal_term->attentionDays()))) {
            $reminders[] = [
                "renewal:{$renewal->toDateString()}",
                FollowUpType::Call,
                "Renewal: {$policy->product_name} renews {$renewal->format('M j')}",
                $renewal->subDays(min(self::RENEWAL_LEAD_DAYS, $policy->renewal_term->attentionDays())),
            ];
        }

        $premium = $policy->nextPaymentDue($today);
        if ($premium && $premium->lessThanOrEqualTo($today->addDays(ListPoliciesNeedingAttention::PAYMENT_WINDOW_DAYS))) {
            $reminders[] = [
                "premium:{$premium->toDateString()}",
                FollowUpType::Message,
                "Premium due: {$policy->product_name} on {$premium->format('M j')}",
                $premium->subDays(self::PREMIUM_LEAD_DAYS),
            ];
        }

        return $reminders;
    }
}
