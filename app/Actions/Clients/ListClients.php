<?php

declare(strict_types=1);

namespace App\Actions\Clients;

use App\DataTransferObject\Clients\ClientFiltersData;
use App\Models\Client;
use App\Models\Workspace;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class ListClients
{
    /**
     * The workspace's clients, newest first.
     *
     * @return LengthAwarePaginator<int, Client>
     */
    public function handle(Workspace $workspace, ClientFiltersData $filters): LengthAwarePaginator
    {
        return Cache::tags("workspace:{$workspace->id}")->rememberForever("clients:{$workspace->id}:index:".md5(json_encode($filters)), function () use ($workspace, $filters) {
            return $workspace->clients()
                ->when($filters->search, function (Builder $query, string $search) {
                    $query->where(function (Builder $query) use ($search) {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
                })
                ->latest()
                ->latest('id')
                ->paginate($filters->perPage, page: $filters->page)
                ->withQueryString();
        });
    }
}
