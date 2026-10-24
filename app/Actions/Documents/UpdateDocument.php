<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Actions\Notifications\AnnounceWorkspaceChange;
use App\DataTransferObject\Documents\DocumentData;
use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
use App\Models\Document;
use Illuminate\Support\Facades\DB;

class UpdateDocument
{
    public function __construct(private AnnounceWorkspaceChange $announceWorkspaceChange) {}

    /**
     * Changes the document's details. The file itself stays; upload a new document to replace it.
     */
    public function handle(Document $document, DocumentData $data): Document
    {
        DB::transaction(fn () => $document->update($data->toArray()));

        $this->announceWorkspaceChange->handle($document->workspace_id, new WorkspaceChangeData(ChangeTopic::Documents, ChangeAction::Updated, $document->id, $document->name));

        return $document;
    }
}
