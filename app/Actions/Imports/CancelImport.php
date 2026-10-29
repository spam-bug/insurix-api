<?php

declare(strict_types=1);

namespace App\Actions\Imports;

use App\Models\Import;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CancelImport
{
    /** Drops an import that hasn't run, with its file and checked rows. */
    public function handle(Import $import): void
    {
        if ($import->status->isBusy()) {
            throw ValidationException::withMessages(['import' => 'Wait for this import to finish its current step.']);
        }

        DB::transaction(fn () => $import->delete());

        Storage::disk(Import::DISK)->delete($import->path);
    }
}
