<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\DataTransferObject\Documents\DocumentData;
use App\Models\Document;
use Illuminate\Support\Facades\DB;

class UpdateDocument
{
    /**
     * Changes the document's details. The file itself stays; upload a new document to replace it.
     */
    public function handle(Document $document, DocumentData $data): Document
    {
        DB::transaction(fn () => $document->update($data->toAttributes()));

        return $document;
    }
}
