<?php

declare(strict_types=1);

namespace App\Actions\ServiceRequests;

use App\DataTransferObject\ServiceRequests\ServiceRequestFiltersData;
use App\Enums\ServiceRequestView;
use App\Http\Resources\Api\V1\ServiceRequestResource;
use App\Models\ServiceRequest;
use App\Models\Workspace;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class ListServiceRequests
{
    /**
     * Unresolved requests come most urgent first; resolved ones newest resolved first.
     *
     * @return LengthAwarePaginator<int, ServiceRequest>
     */
    public function handle(Workspace $workspace, ServiceRequestFiltersData $filters): LengthAwarePaginator
    {
        $today = CarbonImmutable::now($workspace->timezone)->startOfDay();

        return Cache::tags("workspace:{$workspace->id}")->remember("service-requests:{$workspace->id}:index:{$today->toDateString()}:".md5(json_encode($filters)), now()->addHour(), function () use ($workspace, $filters, $today) {
            $query = $workspace->serviceRequests()
                ->with(ServiceRequestResource::RELATIONS)
                ->inView($filters->view, $today)
                ->search($filters->search)
                ->when($filters->clientId, fn (Builder $query, string $clientId) => $query->where('client_id', $clientId));

            $filters->view === ServiceRequestView::Resolved
                ? $query->latest('resolved_at')->latest('id')
                : $query->mostUrgentFirst();

            return $query->paginate($filters->perPage, page: $filters->page)->withQueryString();
        });
    }
}
