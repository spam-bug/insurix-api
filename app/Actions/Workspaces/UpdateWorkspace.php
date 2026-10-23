<?php

declare(strict_types=1);

namespace App\Actions\Workspaces;

use App\DataTransferObject\Workspaces\WorkspaceSettingsData;
use App\Models\Workspace;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class UpdateWorkspace
{
    /**
     * The workspace's name, the timezone its day starts in, and the bio on its lead form.
     */
    public function handle(Workspace $workspace, WorkspaceSettingsData $data): Workspace
    {
        DB::transaction(fn () => $workspace->update($data->toArray()));

        Cache::tags("workspace:{$workspace->id}")->flush();

        return $workspace;
    }
}
