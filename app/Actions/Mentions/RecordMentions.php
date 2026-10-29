<?php

declare(strict_types=1);

namespace App\Actions\Mentions;

use App\Models\Note;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class RecordMentions
{
    /** A mention in a note's text: `@[Ana Reyes](user:{id})`. The name is only for display. */
    public const PATTERN = '/@\[([^\]]+)\]\(user:([0-9a-f-]{36})\)/i';

    /**
     * Brings the note's mentions in line with its text: members of the note's workspace only,
     * never the author. Mentions edited out are dropped. Runs inside the caller's transaction.
     *
     * @return Collection<int, User> the members mentioned for the first time, to tell
     */
    public function handle(Note $note, User $author): Collection
    {
        preg_match_all(self::PATTERN, $note->body, $matches);

        $userIds = array_values(array_diff(array_unique(array_map('strtolower', $matches[2])), [$author->id]));
        $members = $userIds === []
            ? new Collection
            : $note->workspace->users()->whereKey($userIds)->get(['users.id', 'users.name', 'users.email']);

        $note->mentions()->whereNotIn('user_id', $members->modelKeys())->delete();
        $alreadyMentioned = $note->mentions()->pluck('user_id')->all();
        $newlyMentioned = $members->reject(fn (User $member) => in_array($member->id, $alreadyMentioned, true));

        $note->mentions()->createMany($newlyMentioned->map(fn (User $member) => [
            'workspace_id' => $note->workspace_id,
            'user_id' => $member->id,
            'mentioned_by' => $author->id,
        ]));

        return $newlyMentioned->values();
    }
}
