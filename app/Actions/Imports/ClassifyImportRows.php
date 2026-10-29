<?php

declare(strict_types=1);

namespace App\Actions\Imports;

use App\Enums\ImportKind;
use App\Enums\ImportRowStatus;
use App\Enums\ImportStatus;
use App\Models\Import;
use App\Support\ImportValue;
use App\Support\Spreadsheet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ClassifyImportRows
{
    /** Rows are written in batches of this size. */
    private const BATCH = 500;

    public function __construct(private CleanImportRow $cleanImportRow) {}

    /**
     * Reads every row through the agent's column choices and sorts it: valid, invalid (with the
     * problem per field), or a duplicate of someone already in the workspace (same email or
     * phone). A client repeated further down the file with another policy adds that policy to
     * the first row's client; repeated without one, it's a duplicate of that row.
     */
    public function handle(Import $import): void
    {
        $existing = $this->existingPeople($import);
        $seen = [];
        $batch = [];
        $counts = ['total_rows' => 0, 'valid_rows' => 0, 'invalid_rows' => 0, 'duplicate_rows' => 0];
        $extension = pathinfo($import->path, PATHINFO_EXTENSION);
        $rows = Spreadsheet::rows(Storage::disk(Import::DISK)->path($import->path), $extension);

        DB::transaction(function () use ($import, $rows, $existing, &$seen, &$batch, &$counts) {
            $import->rows()->delete();
            $header = true;

            foreach ($rows as $rowNumber => $row) {
                if ($header) {
                    $header = false;

                    continue;
                }

                $cleaned = $this->cleanImportRow->handle($import->kind, $import->mapping, $row);
                $keys = $this->keys($cleaned['data']);
                $match = $this->firstMatch($keys, $existing);
                $earlierRow = $this->firstMatch($keys, $seen);
                $addsPolicy = $import->kind === ImportKind::Clients && isset($cleaned['data']['policy']);

                $status = match (true) {
                    $cleaned['errors'] !== [] => ImportRowStatus::Invalid,
                    $match !== null => ImportRowStatus::Duplicate,
                    $earlierRow !== null && ! $addsPolicy => ImportRowStatus::Duplicate,
                    default => ImportRowStatus::Valid,
                };

                if ($status !== ImportRowStatus::Invalid && $earlierRow === null) {
                    foreach ($keys as $key) {
                        $seen[$key] = $rowNumber;
                    }
                }

                $counts['total_rows']++;
                $counts[$status->value.'_rows']++;
                $batch[] = [
                    'id' => str()->uuid7()->toString(),
                    'import_id' => $import->id,
                    'row_number' => $rowNumber,
                    'status' => $status->value,
                    'values' => json_encode($cleaned['values']),
                    'data' => json_encode($cleaned['data']),
                    'errors' => $cleaned['errors'] === [] ? null : json_encode($cleaned['errors']),
                    'duplicate_id' => $match['id'] ?? null,
                    'duplicate_name' => $match['name'] ?? null,
                    'same_person_as' => $match === null ? $earlierRow : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (count($batch) === self::BATCH) {
                    $import->rows()->insert($batch);
                    $batch = [];
                }
            }

            if ($batch !== []) {
                $import->rows()->insert($batch);
            }

            $import->update([...$counts, 'status' => ImportStatus::Ready]);
        });
    }

    /**
     * The workspace's people this import could repeat, by email and by phone. Leads are also
     * checked against clients, so someone already a client isn't brought back as a lead.
     *
     * @return array<string, array{id: string, name: string}>
     */
    private function existingPeople(Import $import): array
    {
        $people = [];
        $queries = [$import->workspace->clients()];

        if ($import->kind === ImportKind::Leads) {
            array_unshift($queries, $import->workspace->leads());
        }

        foreach ($queries as $query) {
            foreach ($query->get(['id', 'name', 'email', 'phone']) as $person) {
                foreach ($this->keys(['email' => $person->email, 'phone' => $person->phone]) as $key) {
                    $people[$key] ??= ['id' => $person->id, 'name' => $person->name];
                }
            }
        }

        return $people;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, string>
     */
    private function keys(array $data): array
    {
        return array_values(array_filter([
            isset($data['email']) ? 'email:'.mb_strtolower($data['email']) : null,
            isset($data['phone']) ? 'phone:'.ImportValue::phoneKey($data['phone']) : null,
        ]));
    }

    /**
     * @template T
     *
     * @param  array<int, string>  $keys
     * @param  array<string, T>  $index
     * @return T|null
     */
    private function firstMatch(array $keys, array $index): mixed
    {
        foreach ($keys as $key) {
            if (isset($index[$key])) {
                return $index[$key];
            }
        }

        return null;
    }
}
