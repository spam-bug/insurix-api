<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Imports\SaveImportRows;
use App\Enums\ImportStatus;
use App\Models\Import;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/** Saves an import's checked rows in the background, a batch at a time. */
class ImportSpreadsheetRows implements ShouldQueue
{
    use Queueable;

    public int $timeout = 900;

    public int $tries = 1;

    public function __construct(public Import $import) {}

    public function handle(SaveImportRows $saveImportRows): void
    {
        $saveImportRows->handle($this->import);
    }

    public function failed(Throwable $exception): void
    {
        $this->import->update(['status' => ImportStatus::Failed, 'failure' => 'The import stopped partway. The rows saved so far are kept; the rest weren’t brought in.']);
    }
}
