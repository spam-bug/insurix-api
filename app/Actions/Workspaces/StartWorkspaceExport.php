<?php

declare(strict_types=1);

namespace App\Actions\Workspaces;

use App\Jobs\BuildWorkspaceExport;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceExport;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StartWorkspaceExport
{
    /** Records the request and builds the zip in the background; the bell says when it's ready. */
    public function handle(Workspace $workspace, User $owner): WorkspaceExport
    {
        $export = DB::transaction(fn () => $workspace->exports()->create(['requested_by' => $owner->id]));

        Cache::tags("workspace:{$workspace->id}")->flush();

        BuildWorkspaceExport::dispatch($export);

        return $export;
    }
}
