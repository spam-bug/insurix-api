<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadDocument
{
    /**
     * The file itself, saved under the document's name: as an attachment, or inline for the
     * preview (the browser shows images and PDFs itself).
     */
    public function handle(Document $document, bool $inline = false): StreamedResponse
    {
        $disk = Storage::disk(Document::DISK);

        abort_unless($disk->exists($document->path), 404, 'This file is missing. Upload it again.');

        return $inline
            ? $disk->response($document->path, $document->downloadName(), ['Content-Type' => $document->mime_type])
            : $disk->download($document->path, $document->downloadName(), ['Content-Type' => $document->mime_type]);
    }
}
