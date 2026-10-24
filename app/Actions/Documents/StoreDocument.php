<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Actions\Notifications\AnnounceWorkspaceChange;
use App\DataTransferObject\Documents\DocumentData;
use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
use App\Enums\WorkspaceRole;
use App\Models\Document;
use App\Models\Workspace;
use App\Notifications\StorageAlmostFullNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class StoreDocument
{
    /** The owner hears about storage once an upload takes usage past this share of the limit. */
    private const ALMOST_FULL = 0.9;

    public function __construct(private AnnounceWorkspaceChange $announceWorkspaceChange, private MeasureStorage $measureStorage) {}

    /**
     * Keeps an uploaded file in the workspace, for a client or unassigned in the inbox. Without
     * a name, the document is named after the file. The storage limit is checked by the request;
     * the upload that takes usage past 90% of it tells the owner through the bell.
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

        $this->announceWorkspaceChange->handle($workspace->id, new WorkspaceChangeData(ChangeTopic::Documents, ChangeAction::Created, $document->id, $document->name));

        ['used' => $used, 'limit' => $limit] = $this->measureStorage->handle($workspace);
        $threshold = $limit * self::ALMOST_FULL;

        if ($used >= $threshold && $used - $document->size < $threshold) {
            Notification::send(
                $workspace->users()->wherePivot('role', WorkspaceRole::Owner->value)->get(),
                new StorageAlmostFullNotification($workspace->id, $used, $limit),
            );
        }

        return $document;
    }
}
