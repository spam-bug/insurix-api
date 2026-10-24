<?php

declare(strict_types=1);

namespace App\Actions\FollowUps;

use App\Enums\FollowUpType;
use App\Models\Workspace;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

class SummarizeFollowUps
{
    /**
     * Counts of the agent's open follow-ups: overdue, still due later today, and due in the
     * seven days after today. Also how many were done since Monday, in the workspace's timezone,
     * and the open ones per type (every type, zero included).
     *
     * @return array{overdue: int, due_today: int, upcoming: int, done_this_week: int, open_by_type: array<string, int>}
     */
    public function handle(Workspace $workspace): array
    {
        return Cache::tags("workspace:{$workspace->id}")->remember("follow-ups:{$workspace->id}:summary", now()->addMinutes(5), function () use ($workspace) {
            $now = CarbonImmutable::now();
            $endOfToday = CarbonImmutable::now($workspace->timezone)->endOfDay()->utc();
            $endOfWeek = $endOfToday->addDays(7);

            $counts = $workspace->followUps()
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
                'done_this_week' => $workspace->followUps()
                    ->where('completed_at', '>=', CarbonImmutable::now($workspace->timezone)->startOfWeek()->utc())
                    ->count(),
                'open_by_type' => collect(FollowUpType::cases())
                    ->mapWithKeys(fn (FollowUpType $type) => [$type->value => 0])
                    ->merge($workspace->followUps()->whereNull('completed_at')->toBase()->selectRaw('type, count(*) as aggregate')->groupBy('type')->pluck('aggregate', 'type')->map(fn ($count) => (int) $count))
                    ->all(),
            ];
        });
    }
}
