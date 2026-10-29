<?php

declare(strict_types=1);

namespace App\Actions\Notes;

use App\Actions\Leads\ScoreLead;
use App\Actions\Notifications\AnnounceWorkspaceChange;
use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
use App\Models\Note;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DeleteNote
{
    public function __construct(private AnnounceWorkspaceChange $announceWorkspaceChange, private ScoreLead $scoreLead) {}

    /** Takes the note out of its lead's timeline too, so the lead's score is brought up to date. */
    public function handle(Note $note): void
    {
        DB::transaction(function () use ($note) {
            $note->mentions()->delete();
            $note->delete();
            $note->leadActivity?->delete();

            if ($note->lead) {
                $this->scoreLead->handle($note->lead);
            }
        });

        $this->announceWorkspaceChange->handle($note->workspace_id, new WorkspaceChangeData(ChangeTopic::Notes, ChangeAction::Deleted, $note->id, Str::limit($note->body, 40)));
    }
}
