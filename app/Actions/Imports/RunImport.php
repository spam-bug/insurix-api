<?php

declare(strict_types=1);

namespace App\Actions\Imports;

use App\Enums\ImportStatus;
use App\Jobs\ImportSpreadsheetRows;
use App\Models\Import;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RunImport
{
    /** Starts saving the checked rows in the background, skipping or filling in duplicates as chosen. */
    public function handle(Import $import, bool $updateDuplicates): Import
    {
        if ($import->status !== ImportStatus::Ready) {
            throw ValidationException::withMessages(['import' => 'This import isn’t ready to run.']);
        }
        if ($import->valid_rows === 0 && ! ($updateDuplicates && $import->duplicate_rows > 0)) {
            throw ValidationException::withMessages(['import' => 'There are no rows to bring in.']);
        }

        DB::transaction(fn () => $import->update(['status' => ImportStatus::Importing, 'update_duplicates' => $updateDuplicates]));

        ImportSpreadsheetRows::dispatch($import);

        return $import;
    }
}
