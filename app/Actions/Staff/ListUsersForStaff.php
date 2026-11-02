<?php

declare(strict_types=1);

namespace App\Actions\Staff;

use App\DataTransferObject\Staff\StaffSearchFilters;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ListUsersForStaff
{
    /**
     * Agents, newest first, found by name or email, with the workspaces they're in.
     *
     * @return LengthAwarePaginator<int, User>
     */
    public function handle(StaffSearchFilters $filters): LengthAwarePaginator
    {
        return User::query()
            ->select(['id', 'name', 'email', 'email_verified_at', 'two_factor_confirmed_at', 'created_at'])
            ->when($filters->search, fn (Builder $query, string $search) => $query->where(fn (Builder $query) => $query
                ->whereLike('name', "%{$search}%")
                ->orWhereLike('email', "%{$search}%")))
            ->with(['workspaces' => fn ($query) => $query->select(['workspaces.id', 'workspaces.name'])])
            ->latest()
            ->paginate(25, page: $filters->page)
            ->withQueryString();
    }
}
