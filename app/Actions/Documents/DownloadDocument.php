<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadDocument
{
    /**
     * The file itself, as an attachment saved under the document's name.
     */
    public function handle(Document $document): StreamedResponse
    {
        $disk = Storage::disk(Document::DISK);

        abort_unless($disk->exists($document->path), 404, 'This file is missing. Upload it again.');

        return $disk->download($document->path, $document->downloadName(), ['Content-Type' => $document->mime_type]);
    }
}
