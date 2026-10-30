<?php

declare(strict_types=1);

namespace App\Actions\Forms;

use App\Enums\FormResponseStatus;
use App\Enums\ImportKind;
use App\Enums\ImportRowStatus;
use App\Models\FormResponse;
use App\Models\Import;
use Illuminate\Support\Facades\DB;

class MarkResponsesAdded
{
    /**
     * After an import made from form responses: each response whose row became or updated a lead
     * or client is marked added and linked to it. Skipped and invalid rows leave theirs as new.
     */
    public function handle(Import $import): void
    {
        $column = $import->kind === ImportKind::Leads ? 'lead_id' : 'client_id';
        $records = $import->rows()
            ->whereIn('status', [ImportRowStatus::Imported, ImportRowStatus::Updated])
            ->whereNotNull('record_id')
            ->pluck('record_id', 'row_number');

        DB::transaction(function () use ($import, $column, $records) {
            foreach ($records as $rowNumber => $recordId) {
                $responseId = $import->responses[$rowNumber] ?? null;

                if ($responseId !== null) {
                    FormResponse::query()->whereKey($responseId)->update([$column => $recordId, 'status' => FormResponseStatus::Added]);
                }
            }
        });
    }
}
