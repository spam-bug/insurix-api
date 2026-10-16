<?php

namespace App\Actions\Leads;

use App\DataTransferObjects\Leads\LeadFiltersData;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ListLeads
{
    /**
     * @return LengthAwarePaginator<int, Lead>
     */
    public function handle(User $agent, LeadFiltersData $filters): LengthAwarePaginator
    {
        return $agent->leads()
            ->when($filters->search, function (Builder $query, string $search) {
                $query->where(function (Builder $query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($filters->status, fn (Builder $query, $status) => $query->where('status', $status))
            ->when($filters->interest, fn (Builder $query, $interest) => $query->where('interest', $interest))
            ->latest()
            ->latest('id')
            ->paginate($filters->perPage)
            ->withQueryString();
    }
}
