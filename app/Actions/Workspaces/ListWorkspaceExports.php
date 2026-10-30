<?php

declare(strict_types=1);

namespace App\Actions\Workspaces;

use App\Models\Workspace;
use App\Models\WorkspaceExport;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class ListWorkspaceExports
{
    /**
     * Exports still kept, newest first. Writes flush the workspace's cache, including when the
     * background job finishes.
     *
     * @return Collection<int, WorkspaceExport>
     */
    public function handle(Workspace $workspace): Collection
    {
        return Cache::tags("workspace:{$workspace->id}")->rememberForever("exports:{$workspace->id}:index", fn () => $workspace->exports()
            ->with('requester:id,name')
            ->where('created_at', '>', now()->subDays(WorkspaceExport::KEEP_DAYS))
            ->latest()
            ->get());
    }
}
