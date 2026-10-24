<?php

declare(strict_types=1);

namespace App\Actions\FollowUps;

use App\DataTransferObject\FollowUps\FollowUpFiltersData;
use App\Enums\FollowUpWindow;
use App\Models\FollowUp;
use App\Models\Workspace;
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
    public function handle(Workspace $workspace, FollowUpFiltersData $filters): LengthAwarePaginator
    {
        return Cache::tags("workspace:{$workspace->id}")->remember("follow-ups:{$workspace->id}:index:".md5(json_encode($filters)), now()->addMinutes(5), function () use ($workspace, $filters) {
            $endOfToday = CarbonImmutable::now($workspace->timezone)->endOfDay()->utc();

            $query = $workspace->followUps()
                ->with(['lead:id,name,status', 'client:id,name'])
                ->when($filters->leadId, fn (Builder $query, string $leadId) => $query->where('lead_id', $leadId))
                ->when($filters->clientId, fn (Builder $query, string $clientId) => $query->where('client_id', $clientId));

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
