<?php

declare(strict_types=1);

namespace App\Actions\Workspaces;

use App\Models\Document;
use App\Models\Import;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceExport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PurgeWorkspace
{
    /**
     * Removes a workspace for good: its rows (everything it owns cascades in the database), then
     * its files. Files go after the rows commit, so a failed delete never leaves records
     * pointing at missing files.
     */
    public function handle(Workspace $workspace): void
    {
        DB::transaction(fn () => $workspace->forceDelete());

        Storage::disk(Document::DISK)->deleteDirectory("documents/{$workspace->id}");
        Storage::disk(Import::DISK)->deleteDirectory("imports/{$workspace->id}");
        Storage::disk(WorkspaceExport::DISK)->deleteDirectory("exports/{$workspace->id}");

        if ($workspace->logo_path) {
            Storage::disk(User::UPLOADS_DISK)->delete($workspace->logo_path);
        }
    }
}
