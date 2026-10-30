<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Workspaces\WriteWorkspaceExport;
use App\Enums\ExportStatus;
use App\Models\WorkspaceExport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Throwable;

/** Builds an export in the background, so a large workspace doesn't hold up the request. */
class BuildWorkspaceExport implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public int $tries = 1;

    public function __construct(public WorkspaceExport $export) {}

    public function handle(WriteWorkspaceExport $writeWorkspaceExport): void
    {
        $writeWorkspaceExport->handle($this->export);
    }

    public function failed(Throwable $exception): void
    {
        $this->export->update(['status' => ExportStatus::Failed]);

        Cache::tags("workspace:{$this->export->workspace_id}")->flush();
    }
}
