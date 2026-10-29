<?php

declare(strict_types=1);

namespace App\Actions\Notes;

use App\Actions\Mentions\RecordMentions;
use App\Actions\Notifications\AnnounceWorkspaceChange;
use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
use App\Models\Note;
use App\Notifications\MentionedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class UpdateNote
{
    public function __construct(private AnnounceWorkspaceChange $announceWorkspaceChange, private RecordMentions $recordMentions) {}

    /** Rewrites the text; only people mentioned for the first time are told. */
    public function handle(Note $note, string $body): Note
    {
        $mentioned = DB::transaction(function () use ($note, $body) {
            $note->update(['body' => $body]);

            return $this->recordMentions->handle($note, $note->user);
        });

        Notification::send($mentioned, new MentionedNotification($note, $note->user));

        $this->announceWorkspaceChange->handle($note->workspace_id, new WorkspaceChangeData(ChangeTopic::Notes, ChangeAction::Updated, $note->id, Str::limit($note->body, 40)));

        return $note;
    }
}
