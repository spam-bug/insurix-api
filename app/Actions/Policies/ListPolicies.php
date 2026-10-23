<?php

declare(strict_types=1);

namespace App\Actions\Policies;

use App\DataTransferObject\Policies\PolicyFiltersData;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class ListPolicies
{
    /**
     * The agent's policies, newest first. Search matches the policy number,
     * product or client name.
     *
     * @return LengthAwarePaginator<int, Policy>
     */
    public function handle(User $agent, PolicyFiltersData $filters): LengthAwarePaginator
    {
        return Cache::tags("agent:{$agent->id}")->rememberForever("policies:{$agent->id}:index:".md5(json_encode($filters)), function () use ($agent, $filters) {
            return $agent->policies()
                ->with('client:id,name')
                ->when($filters->clientId, fn (Builder $query, string $clientId) => $query->where('client_id', $clientId))
                ->when($filters->productId, fn (Builder $query, string $productId) => $query->where('product_id', $productId))
                ->when($filters->status, fn (Builder $query, $status) => $query->where('status', $status))
                ->when($filters->search, function (Builder $query, string $search) {
                    $query->where(function (Builder $query) use ($search) {
                        $query->where('policy_number', 'like', "%{$search}%")
                            ->orWhere('product_name', 'like', "%{$search}%")
                            ->orWhereHas('client', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"));
                    });
                })
                ->latest()
                ->latest('id')
                ->paginate($filters->perPage, page: $filters->page)
                ->withQueryString();
        });
    }
}
