<?php

declare(strict_types=1);

namespace App\Actions\Notes;

use App\Actions\Notifications\AnnounceWorkspaceChange;
use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
use App\Models\Note;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PinNote
{
    public function __construct(private AnnounceWorkspaceChange $announceWorkspaceChange) {}

    /** Pinned notes stay on top of the notes list for everyone in the workspace. */
    public function handle(Note $note, bool $pinned): Note
    {
        DB::transaction(fn () => $note->update(['pinned_at' => $pinned ? now() : null]));

        $this->announceWorkspaceChange->handle($note->workspace_id, new WorkspaceChangeData(ChangeTopic::Notes, ChangeAction::Updated, $note->id, Str::limit($note->body, 40)));

        return $note;
    }
}
