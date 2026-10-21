<?php

declare(strict_types=1);

namespace App\Actions\FollowUps;

use App\DataTransferObject\FollowUps\FollowUpFiltersData;
use App\Enums\FollowUpWindow;
use App\Models\FollowUp;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class ListFollowUps
{
    /**
     * Open follow-ups come soonest first; completed ones most recently done first.
     *
     * @return LengthAwarePaginator<int, FollowUp>
     */
    public function handle(User $agent, FollowUpFiltersData $filters): LengthAwarePaginator
    {
        return Cache::tags("agent:{$agent->id}")->remember("follow-ups:{$agent->id}:index:".md5(json_encode($filters)), now()->addMinutes(5), function () use ($agent, $filters) {
            $endOfToday = CarbonImmutable::now($agent->timezone)->endOfDay()->utc();

            $query = $agent->followUps()
                ->with(['lead:id,name,status', 'lead.client:id,lead_id'])
                ->when($filters->leadId, fn (Builder $query, string $leadId) => $query->where('lead_id', $leadId));

            match ($filters->window) {
                FollowUpWindow::Today => $query->whereNull('completed_at')->where('due_at', '<=', $endOfToday),
                FollowUpWindow::Upcoming => $query->whereNull('completed_at')->where('due_at', '>', $endOfToday),
                FollowUpWindow::Open => $query->whereNull('completed_at'),
                FollowUpWindow::Completed => $query->whereNotNull('completed_at'),
            };

            if ($filters->window === FollowUpWindow::Completed) {
                $query->latest('completed_at')->latest('id');
            } else {
                $query->orderBy('due_at')->orderBy('id');
            }

            return $query->paginate($filters->perPage, page: $filters->page)->withQueryString();
        });
    }
}
