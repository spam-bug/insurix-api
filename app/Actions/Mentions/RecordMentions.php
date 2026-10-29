<?php

declare(strict_types=1);

namespace App\Actions\Mentions;

use App\Models\LeadActivity;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Collection;

class RecordMentions
{
    /** A mention in a note's text: `@[Ana Reyes](user:{id})`. The name is only for display. */
    public const PATTERN = '/@\[([^\]]+)\]\(user:([0-9a-f-]{36})\)/i';

    /**
     * Stores who the note mentions: members of the note's workspace only, never its author.
     * Runs inside the caller's transaction.
     *
     * @return Collection<int, User> the members to tell
     */
    public function handle(LeadActivity $note, string $workspaceId, User $author): Collection
    {
        preg_match_all(self::PATTERN, (string) $note->body, $matches);

        $userIds = array_diff(array_unique(array_map('strtolower', $matches[2])), [$author->id]);

        if ($userIds === []) {
            return new Collection;
        }

        $members = Workspace::findOrFail($workspaceId)->users()->whereKey($userIds)->get(['users.id', 'users.name', 'users.email']);

        $note->mentions()->createMany($members->map(fn (User $member) => [
            'workspace_id' => $workspaceId,
            'user_id' => $member->id,
            'mentioned_by' => $author->id,
        ]));

        return $members;
    }
}
