<?php

declare(strict_types=1);

namespace App\Actions\Workspaces;

use App\Enums\ExportStatus;
use App\Models\WorkspaceExport;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadWorkspaceExport
{
    /** The zip, named after the workspace and the day it was made. */
    public function handle(WorkspaceExport $export): StreamedResponse
    {
        $disk = Storage::disk(WorkspaceExport::DISK);

        abort_unless($export->status === ExportStatus::Ready && $disk->exists($export->path), 404, 'This export isn’t ready, or it has expired.');

        return $disk->download($export->path, Str::slug($export->workspace->name).'-export-'.$export->completed_at->toDateString().'.zip');
    }
}
