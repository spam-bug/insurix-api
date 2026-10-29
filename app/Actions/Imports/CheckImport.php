<?php

declare(strict_types=1);

namespace App\Actions\Imports;

use App\Enums\ImportStatus;
use App\Jobs\CheckImportRows;
use App\Models\Import;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckImport
{
    /**
     * Saves the agent's column choices and starts checking every row in the background. Choices
     * can be changed again until the import runs.
     *
     * @param  array<string, int>  $mapping  field => column position
     */
    public function handle(Import $import, array $mapping): Import
    {
        if (! in_array($import->status, [ImportStatus::Mapping, ImportStatus::Ready, ImportStatus::Failed], true)) {
            throw ValidationException::withMessages(['mapping' => 'This import is already running.']);
        }

        DB::transaction(fn () => $import->update(['mapping' => $mapping, 'status' => ImportStatus::Checking, 'failure' => null]));

        CheckImportRows::dispatch($import);

        return $import;
    }
}
