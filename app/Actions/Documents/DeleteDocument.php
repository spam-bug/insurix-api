<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Models\Document;
use Illuminate\Support\Facades\Storage;

class DeleteDocument
{
    /**
     * Deletes the document and its file.
     */
    public function handle(Document $document): void
    {
        $document->delete();

        Storage::disk(Document::DISK)->delete($document->path);
    }
}
