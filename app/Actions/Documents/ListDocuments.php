<?php

namespace App\Actions\Documents;

use App\DataTransferObjects\Documents\DocumentFiltersData;
use App\Models\Document;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ListDocuments
{
    /**
     * The agent's documents, newest first. Search matches the document, file or client name.
     * With $filters->expiring, only ones that have expired or expire within
     * Document::EXPIRING_DAYS, soonest first.
     *
     * @return LengthAwarePaginator<int, Document>
     */
    public function handle(User $agent, DocumentFiltersData $filters): LengthAwarePaginator
    {
        $query = $agent->documents()
            ->with(['client:id,name', 'policy:id,product_name,policy_number'])
            ->when($filters->clientId, fn (Builder $query, int $clientId) => $query->where('client_id', $clientId))
            ->when($filters->policyId, fn (Builder $query, int $policyId) => $query->where('policy_id', $policyId))
            ->when($filters->category, fn (Builder $query, $category) => $query->where('category', $category))
            ->when($filters->search, function (Builder $query, string $search) {
                $query->where(function (Builder $query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('original_name', 'like', "%{$search}%")
                        ->orWhereHas('client', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"));
                });
            });

        if ($filters->expiring) {
            $query->whereNotNull('expires_on')
                ->where('expires_on', '<=', CarbonImmutable::now($agent->timezone)->addDays(Document::EXPIRING_DAYS)->toDateString())
                ->orderBy('expires_on')
                ->orderBy('id');
        } else {
            $query->latest()->latest('id');
        }

        return $query->paginate($filters->perPage)->withQueryString();
    }
}
