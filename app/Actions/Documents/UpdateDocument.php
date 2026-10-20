<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\DataTransferObjects\Documents\DocumentData;
use App\Models\Document;

class UpdateDocument
{
    /**
     * Changes the document's details. The file itself stays; upload a new document to replace it.
     */
    public function handle(Document $document, DocumentData $data): Document
    {
        $document->update($data->toAttributes());

        return $document;
    }
}
