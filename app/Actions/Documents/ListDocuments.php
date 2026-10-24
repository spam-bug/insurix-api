<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\DataTransferObject\Documents\DocumentFiltersData;
use App\Models\Document;
use App\Models\Workspace;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class ListDocuments
{
    /**
     * The workspace's documents, newest first. Search matches the document, file or client name.
     *
     * @return LengthAwarePaginator<int, Document>
     */
    public function handle(Workspace $workspace, DocumentFiltersData $filters): LengthAwarePaginator
    {
        return Cache::tags("workspace:{$workspace->id}")->rememberForever("documents:{$workspace->id}:index:".md5(json_encode($filters)), function () use ($workspace, $filters) {
            return $workspace->documents()
                ->with(['client:id,name', 'policy:id,product_name,policy_number'])
                ->when($filters->clientId, fn (Builder $query, string $clientId) => $query->where('client_id', $clientId))
                ->when($filters->policyId, fn (Builder $query, string $policyId) => $query->where('policy_id', $policyId))
                ->when($filters->category, fn (Builder $query, $category) => $query->where('category', $category))
                ->when($filters->search, function (Builder $query, string $search) {
                    $query->where(function (Builder $query) use ($search) {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('original_name', 'like', "%{$search}%")
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
