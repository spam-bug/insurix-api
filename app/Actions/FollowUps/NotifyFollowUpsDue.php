<?php

declare(strict_types=1);

namespace App\Actions\FollowUps;

use App\Models\Workspace;
use App\Notifications\FollowUpsDueNotification;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Notification;

class NotifyFollowUpsDue
{
    /**
     * A morning nudge for everyone in the workspace: how many open follow-ups are due today and
     * how many are overdue. Follow-ups aren't assigned to anyone yet, so it goes to all members.
     * Nothing is sent on a clear day.
     *
     * @return bool whether a notification was sent
     */
    public function handle(Workspace $workspace): bool
    {
        $today = CarbonImmutable::now($workspace->timezone)->startOfDay();

        $counts = $workspace->followUps()
            ->toBase()
            ->whereNull('completed_at')
            ->where('due_at', '<=', $today->endOfDay()->utc())
            ->selectRaw('sum(case when due_at < ? then 1 else 0 end) as overdue, count(*) as total', [$today->utc()])
            ->first();

        $total = (int) $counts->total;
        if ($total === 0) {
            return false;
        }

        $overdue = (int) $counts->overdue;
        Notification::send($workspace->users()->get(), new FollowUpsDueNotification($workspace->id, $total - $overdue, $overdue));

        return true;
    }
}
