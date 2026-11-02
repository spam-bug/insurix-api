<?php

declare(strict_types=1);

namespace App\Actions\Staff;

use App\DataTransferObject\Staff\StaffWorkspaceFilters;
use App\Enums\Plan;
use App\Enums\WorkspaceRole;
use App\Models\Workspace;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ListWorkspacesForStaff
{
    /**
     * Every workspace, newest first, with its owner and a few counts. Searches the workspace
     * name and its members' names and emails. Not cached: staff need what's true right now.
     *
     * @return LengthAwarePaginator<int, Workspace>
     */
    public function handle(StaffWorkspaceFilters $filters): LengthAwarePaginator
    {
        return Workspace::query()
            ->select(['id', 'name', 'plan', 'plan_ends_at', 'created_at', 'deleted_at'])
            ->when($filters->deleted, fn (Builder $query) => $query->onlyTrashed())
            ->when($filters->search, fn (Builder $query, string $search) => $query->where(fn (Builder $query) => $query
                ->whereLike('name', "%{$search}%")
                ->orWhereHas('users', fn (Builder $query) => $query->whereLike('users.name', "%{$search}%")->orWhereLike('users.email', "%{$search}%"))))
            ->when($filters->plan === Plan::Premium, fn (Builder $query) => $query
                ->where('plan', Plan::Premium)
                ->where(fn (Builder $query) => $query->whereNull('plan_ends_at')->orWhere('plan_ends_at', '>', now())))
            ->when($filters->plan === Plan::Free, fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('plan', Plan::Free)
                ->orWhere('plan_ends_at', '<=', now())))
            ->with(['users' => fn ($query) => $query->select(['users.id', 'users.name', 'users.email'])->wherePivot('role', WorkspaceRole::Owner)])
            ->withCount(['members', 'leads', 'clients'])
            ->latest()
            ->paginate(25, page: $filters->page)
            ->withQueryString();
    }
}
