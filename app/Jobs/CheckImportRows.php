<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Imports\ClassifyImportRows;
use App\Enums\ImportStatus;
use App\Models\Import;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/** Checks every row of an import in the background, so a large file doesn't hold up the request. */
class CheckImportRows implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public int $tries = 1;

    public function __construct(public Import $import) {}

    public function handle(ClassifyImportRows $classifyImportRows): void
    {
        $classifyImportRows->handle($this->import);
    }

    public function failed(Throwable $exception): void
    {
        $this->import->update(['status' => ImportStatus::Failed, 'failure' => 'We couldn’t read every row of this file. Check that it’s a CSV or Excel file and try again.']);
    }
}
