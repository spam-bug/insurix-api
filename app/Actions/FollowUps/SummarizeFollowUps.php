<?php

declare(strict_types=1);

namespace App\Actions\FollowUps;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

class SummarizeFollowUps
{
    /**
     * Counts of the agent's open follow-ups: overdue, still due later today, and due in the
     * seven days after today.
     *
     * @return array{overdue: int, due_today: int, upcoming: int}
     */
    public function handle(User $agent): array
    {
        return Cache::tags("agent:{$agent->id}")->remember("follow-ups:{$agent->id}:summary", now()->addMinutes(5), function () use ($agent) {
            $now = CarbonImmutable::now();
            $endOfToday = CarbonImmutable::now($agent->timezone)->endOfDay()->utc();
            $endOfWeek = $endOfToday->addDays(7);

            $counts = $agent->followUps()
                ->whereNull('completed_at')
                ->toBase()
                ->selectRaw('sum(case when due_at < ? then 1 else 0 end) as overdue', [$now])
                ->selectRaw('sum(case when due_at >= ? and due_at <= ? then 1 else 0 end) as due_today', [$now, $endOfToday])
                ->selectRaw('sum(case when due_at > ? and due_at <= ? then 1 else 0 end) as upcoming', [$endOfToday, $endOfWeek])
                ->first();

            return [
                'overdue' => (int) ($counts->overdue ?? 0),
                'due_today' => (int) ($counts->due_today ?? 0),
                'upcoming' => (int) ($counts->upcoming ?? 0),
            ];
        });
    }
}
