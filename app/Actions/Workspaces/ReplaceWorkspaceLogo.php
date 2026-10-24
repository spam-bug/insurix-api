<?php

declare(strict_types=1);

namespace App\Actions\Workspaces;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ReplaceWorkspaceLogo
{
    /**
     * Stores the workspace's new logo and deletes the old one. Passing no file removes it.
     */
    public function handle(Workspace $workspace, ?UploadedFile $file): Workspace
    {
        $previous = $workspace->logo_path;
        $path = $file?->store('logos', User::UPLOADS_DISK);

        DB::transaction(fn () => $workspace->forceFill(['logo_path' => $path])->save());

        if ($previous) {
            Storage::disk(User::UPLOADS_DISK)->delete($previous);
        }

        return $workspace;
    }
}
