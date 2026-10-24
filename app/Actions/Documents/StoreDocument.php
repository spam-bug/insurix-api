<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\DataTransferObject\Documents\DocumentData;
use App\Models\Document;
use App\Models\Workspace;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StoreDocument
{
    /**
     * Keeps an uploaded file in the workspace, for a client or unassigned in the inbox. Without
     * a name, the document is named after the file. The storage limit is checked by the request.
     */
    public function handle(Workspace $workspace, UploadedFile $file, DocumentData $data): Document
    {
        $path = $file->store("documents/{$workspace->id}/".($data->clientId ?? 'unassigned'), Document::DISK);

        $document = DB::transaction(fn () => $workspace->documents()->create([
            ...$data->toArray(),
            'name' => $data->name ?? Str::limit(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), 250, ''),
            'original_name' => Str::limit($file->getClientOriginalName(), 250, ''),
            'path' => $path,
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'size' => $file->getSize(),
        ]));

        Cache::tags("workspace:{$workspace->id}")->flush();

        return $document;
    }
}
