<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\WorkspaceExport;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('exports:prune')]
#[Description('Delete workspace exports older than the days they are kept, with their zips')]
class PruneWorkspaceExports extends Command
{
    public function handle(): int
    {
        $pruned = 0;

        WorkspaceExport::query()
            ->where('created_at', '<=', now()->subDays(WorkspaceExport::KEEP_DAYS))
            ->each(function (WorkspaceExport $export) use (&$pruned) {
                if ($export->path) {
                    Storage::disk(WorkspaceExport::DISK)->delete($export->path);
                }

                $export->delete();
                $pruned++;
            });

        $this->info("Pruned {$pruned} workspace exports.");

        return self::SUCCESS;
    }
}
