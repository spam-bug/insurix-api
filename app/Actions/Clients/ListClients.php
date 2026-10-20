<?php

declare(strict_types=1);

namespace App\Actions\Clients;

use App\DataTransferObjects\Clients\ClientFiltersData;
use App\Models\Client;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ListClients
{
    /**
     * The agent's clients, newest first.
     *
     * @return LengthAwarePaginator<int, Client>
     */
    public function handle(User $agent, ClientFiltersData $filters): LengthAwarePaginator
    {
        return $agent->clients()
            ->when($filters->search, function (Builder $query, string $search) {
                $query->where(function (Builder $query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->latest('id')
            ->paginate($filters->perPage)
            ->withQueryString();
    }
}
