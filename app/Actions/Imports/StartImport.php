<?php

declare(strict_types=1);

namespace App\Actions\Imports;

use App\Enums\ImportField;
use App\Enums\ImportKind;
use App\Enums\ImportStatus;
use App\Models\Import;
use App\Models\User;
use App\Models\Workspace;
use App\Support\Spreadsheet;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StartImport
{
    /**
     * Keeps the uploaded file privately, reads its header row and first few rows, and guesses
     * which column fills which field from the headers.
     */
    public function handle(Workspace $workspace, User $user, ImportKind $kind, UploadedFile $file): Import
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $rows = Spreadsheet::rows($file->getRealPath(), $extension);
        $headers = $rows->current();

        if ($headers === null) {
            throw ValidationException::withMessages(['file' => 'This file has no rows.']);
        }

        $sample = [];
        for ($rows->next(); $rows->valid() && count($sample) < Import::SAMPLE_ROWS; $rows->next()) {
            $sample[] = $rows->current();
        }

        $path = $file->storeAs("imports/{$workspace->id}", str()->uuid().".{$extension}", Import::DISK);

        return DB::transaction(fn () => $workspace->imports()->create([
            'user_id' => $user->id,
            'kind' => $kind,
            'status' => ImportStatus::Mapping,
            'original_name' => $file->getClientOriginalName(),
            'path' => $path,
            'headers' => $headers,
            'sample' => $sample,
            'mapping' => $this->guessMapping($kind, $headers),
        ]));
    }

    /**
     * Each field goes to the first column whose header means it; a column fills one field at most.
     *
     * @param  array<int, string>  $headers
     * @return array<string, int>
     */
    private function guessMapping(ImportKind $kind, array $headers): array
    {
        $mapping = [];

        foreach (ImportField::for($kind) as $field) {
            foreach ($headers as $column => $header) {
                if (! in_array($column, $mapping, true) && in_array(ImportField::normalizeHeader($header), $field->headerNames(), true)) {
                    $mapping[$field->value] = $column;
                    break;
                }
            }
        }

        // A full name column makes separate first and last name columns unnecessary.
        if (isset($mapping['name'])) {
            unset($mapping['first_name'], $mapping['last_name']);
        }

        return $mapping;
    }
}
