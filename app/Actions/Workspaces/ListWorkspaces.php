<?php

declare(strict_types=1);

namespace App\Actions\Workspaces;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Collection;

class ListWorkspaces
{
    /**
     * Every workspace the user belongs to, with their role in each, oldest first.
     *
     * @return Collection<int, Workspace>
     */
    public function handle(User $user): Collection
    {
        return $user->workspaces()->oldest('workspaces.created_at')->get();
    }
}
