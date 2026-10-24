<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Actions\Notifications\AnnounceWorkspaceChange;
use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
use App\Models\Document;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DeleteDocument
{
    public function __construct(private AnnounceWorkspaceChange $announceWorkspaceChange) {}

    /**
     * Deletes the document and its file.
     */
    public function handle(Document $document): void
    {
        DB::transaction(fn () => $document->delete());

        $this->announceWorkspaceChange->handle($document->workspace_id, new WorkspaceChangeData(ChangeTopic::Documents, ChangeAction::Deleted, $document->id, $document->name));

        Storage::disk(Document::DISK)->delete($document->path);
    }
}
