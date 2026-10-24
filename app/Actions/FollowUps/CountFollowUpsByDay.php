<?php

declare(strict_types=1);

namespace App\Actions\FollowUps;

use App\Enums\FollowUpType;
use App\Models\Workspace;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

class CountFollowUpsByDay
{
    /** How many days the week strip shows, starting today. */
    public const DAYS = 7;

    /**
     * Open follow-ups for the week strip: how many are overdue, then how many fall on each of the
     * next days in the workspace's timezone. Today counts only what isn't overdue yet. Grouped
     * in PHP, since turning times into local days in SQL differs per database.
     *
     * @return array{overdue: int, days: array<int, array{date: string, count: int}>}
     */
    public function handle(Workspace $workspace, ?FollowUpType $type = null): array
    {
        return Cache::tags("workspace:{$workspace->id}")->remember("follow-ups:{$workspace->id}:by-day:".($type?->value ?? 'all'), now()->addMinutes(5), function () use ($workspace, $type) {
            $now = CarbonImmutable::now();
            $today = CarbonImmutable::now($workspace->timezone)->startOfDay();
            $open = $workspace->followUps()->whereNull('completed_at')->when($type, fn ($query) => $query->where('type', $type));

            $perDay = (clone $open)
                ->where('due_at', '>=', $now)
                ->where('due_at', '<=', $today->addDays(self::DAYS - 1)->endOfDay()->utc())
                ->pluck('due_at')
                ->countBy(fn (CarbonImmutable $dueAt) => $dueAt->setTimezone($workspace->timezone)->toDateString());

            return [
                'overdue' => (clone $open)->where('due_at', '<', $now)->count(),
                'days' => collect(range(0, self::DAYS - 1))
                    ->map(fn (int $offset) => $today->addDays($offset)->toDateString())
                    ->map(fn (string $date) => ['date' => $date, 'count' => $perDay->get($date, 0)])
                    ->all(),
            ];
        });
    }
}
