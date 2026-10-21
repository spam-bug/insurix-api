<?php

declare(strict_types=1);

namespace App\Actions\Leads;

use App\DataTransferObject\Leads\LeadFiltersData;
use App\Enums\LeadSort;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class ListLeads
{
    /**
     * @return LengthAwarePaginator<int, Lead>
     */
    public function handle(User $agent, LeadFiltersData $filters): LengthAwarePaginator
    {
        return Cache::tags("agent:{$agent->id}")->rememberForever("leads:{$agent->id}:index:".md5(json_encode($filters)), function () use ($agent, $filters) {
            $query = $agent->leads()
                ->with('client:id,lead_id')
                ->when($filters->search, function (Builder $query, string $search) {
                    $query->where(function (Builder $query) use ($search) {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
                })
                ->when($filters->status, fn (Builder $query, $status) => $query->where('status', $status))
                ->when($filters->open && ! $filters->status, fn (Builder $query) => $query->whereIn('status', LeadStatus::open()))
                ->when($filters->interest, fn (Builder $query, $interest) => $query->where('interest', $interest))
                ->when($filters->source, fn (Builder $query, $source) => $query->where('source', $source));

            match ($filters->sort) {
                LeadSort::Latest => $query->latest()->latest('id'),
                LeadSort::Oldest => $query->oldest()->oldest('id'),
                LeadSort::NameAsc => $query->orderBy('name')->orderBy('id'),
                LeadSort::NameDesc => $query->orderByDesc('name')->orderByDesc('id'),
                LeadSort::Score => $query->orderByRaw('score is null')->orderByDesc('score')->latest('id'),
            };

            return $query->paginate($filters->perPage, page: $filters->page)->withQueryString();
        });
    }
}
