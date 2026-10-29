<?php

declare(strict_types=1);

namespace App\Actions\Notes;

use App\DataTransferObject\Notes\NoteFiltersData;
use App\Http\Resources\Api\V1\NoteResource;
use App\Models\Note;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class ListNotes
{
    /**
     * @return LengthAwarePaginator<int, Note>
     */
    public function handle(Workspace $workspace, NoteFiltersData $filters, User $user): LengthAwarePaginator
    {
        return Cache::tags("workspace:{$workspace->id}")->remember("notes:{$workspace->id}:index:{$user->id}:".md5(json_encode($filters)), now()->addMinutes(5), fn () => $workspace->notes()
            ->with(NoteResource::RELATIONS)
            ->linkedTo($filters->link)
            ->search($filters->search)
            ->when($filters->mine, fn (Builder $query) => $query->where('user_id', $user->id))
            ->when($filters->leadId, fn (Builder $query, string $leadId) => $query->where('lead_id', $leadId))
            ->when($filters->clientId, fn (Builder $query, string $clientId) => $query->where('client_id', $clientId))
            ->pinnedFirst()
            ->paginate($filters->perPage, page: $filters->page)
            ->withQueryString());
    }
}
