<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Note;
use App\Models\User;
use App\Policies\Concerns\ChecksMembership;

class NotePolicy
{
    use ChecksMembership;

    /** Only the author rewrites a note. */
    public function update(User $user, Note $note): bool
    {
        return $note->user_id === $user->id;
    }

    /** The author, or the workspace owner tidying up. */
    public function delete(User $user, Note $note): bool
    {
        return $note->user_id === $user->id || $user->can('manage', $note->workspace);
    }

    /** Anyone in the workspace pins a note for everyone. */
    public function pin(User $user, Note $note): bool
    {
        return $this->isMember($user, $note->workspace_id);
    }
}
