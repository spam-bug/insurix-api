<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\DataTransferObjects\Documents\DocumentData;
use App\Models\Client;
use App\Models\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StoreDocument
{
    /**
     * Keeps an uploaded file for the client, owned by the client's agent. Without a name, the
     * document is named after the file.
     */
    public function handle(Client $client, UploadedFile $file, DocumentData $data): Document
    {
        $path = $file->store("documents/{$client->user_id}/{$client->id}", Document::DISK);

        return DB::transaction(fn () => $client->documents()->create([
            'name' => Str::limit(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), 250, ''),
            ...$data->toAttributes(),
            'user_id' => $client->user_id,
            'original_name' => Str::limit($file->getClientOriginalName(), 250, ''),
            'path' => $path,
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'size' => $file->getSize(),
        ]));
    }
}
