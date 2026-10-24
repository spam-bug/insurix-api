<?php

declare(strict_types=1);

namespace App\Actions\Clients;

use App\Actions\Policies\FindNextRenewalDate;
use App\DataTransferObject\Clients\ClientFiltersData;
use App\Enums\ClientSort;
use App\Enums\PolicyStatus;
use App\Models\Client;
use App\Models\FollowUp;
use App\Models\Policy;
use App\Models\Workspace;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class ListClients
{
    public function __construct(private FindNextRenewalDate $findNextRenewalDate) {}

    /**
     * The workspace's clients with what the agent compares them by: policies and annual premium
     * in force, the next renewal and the last completed follow-up. Kept for an hour, since the
     * next renewal moves with the date.
     *
     * @return LengthAwarePaginator<int, Client>
     */
    public function handle(Workspace $workspace, ClientFiltersData $filters): LengthAwarePaginator
    {
        return Cache::tags("workspace:{$workspace->id}")->remember("clients:{$workspace->id}:index:".md5(json_encode($filters)), now()->addHour(), function () use ($workspace, $filters) {
            $inForce = fn (Builder|HasMany $query) => $query->where('status', PolicyStatus::InForce);
            $today = CarbonImmutable::now($workspace->timezone)->startOfDay();

            $query = $workspace->clients()
                ->withCount(['policies as in_force_policies' => $inForce])
                ->withSum(['policies as annual_premium' => $inForce], 'annual_premium')
                ->withMax('followUps as last_contact_at', 'completed_at')
                ->with(['policies' => fn (HasMany $query) => $inForce($query)->select('id', 'client_id', 'status', 'renewal_term', 'effective_date', 'renewal_date')])
                ->when($filters->search, function (Builder $query, string $search) {
                    $query->where(function (Builder $query) use ($search) {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
                });

            match ($filters->sort) {
                ClientSort::Latest => $query->latest(),
                ClientSort::Name => $query->orderBy('name'),
                ClientSort::Premium => $query->orderByDesc(
                    Policy::query()->selectRaw('coalesce(sum(annual_premium), 0)')->whereColumn('client_id', 'clients.id')->where('status', PolicyStatus::InForce),
                ),
                ClientSort::LastContact => $query->orderBy(
                    FollowUp::query()->selectRaw("coalesce(max(completed_at), '1970-01-01')")->whereColumn('client_id', 'clients.id'),
                ),
            };

            return $query->latest('id')
                ->paginate($filters->perPage, page: $filters->page)
                ->withQueryString()
                ->through(fn (Client $client) => $client->setAttribute(
                    'next_renewal_on',
                    $client->policies->map(fn (Policy $policy) => $this->findNextRenewalDate->handle($policy, $today))->filter()->min(),
                )->unsetRelation('policies'));
        });
    }
}
