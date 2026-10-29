<?php

declare(strict_types=1);

namespace App\Actions\Imports;

use App\Enums\ImportStatus;
use App\Jobs\CheckImportRows;
use App\Models\Import;
use App\Models\ImportRow;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApplyImportFixes
{
    /**
     * Accepts every suggested fix and checks all rows again, since a corrected email or phone can
     * make a row a duplicate. Fixes accepted earlier are kept.
     */
    public function handle(Import $import): Import
    {
        if ($import->status !== ImportStatus::Ready) {
            throw ValidationException::withMessages(['import' => 'Fixes can be applied once every row is checked.']);
        }

        $fixes = $import->fixes ?? [];
        $import->rows()->whereNotNull('suggestions')->get(['row_number', 'suggestions'])
            ->each(function (ImportRow $row) use (&$fixes) {
                $fixes[$row->row_number] = [...($fixes[$row->row_number] ?? []), ...$row->suggestions];
            });

        if ($fixes === ($import->fixes ?? [])) {
            throw ValidationException::withMessages(['import' => 'There are no suggested fixes to apply.']);
        }

        DB::transaction(fn () => $import->update(['fixes' => $fixes, 'status' => ImportStatus::Checking]));

        CheckImportRows::dispatch($import);

        return $import;
    }
}
