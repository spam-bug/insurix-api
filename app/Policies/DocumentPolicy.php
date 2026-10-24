<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Document;
use App\Models\User;
use App\Policies\Concerns\ChecksMembership;

class DocumentPolicy
{
    use ChecksMembership;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Document $document): bool
    {
        return $this->isMember($user, $document->workspace_id);
    }

    public function update(User $user, Document $document): bool
    {
        return $this->isMember($user, $document->workspace_id);
    }

    public function delete(User $user, Document $document): bool
    {
        return $this->isMember($user, $document->workspace_id);
    }
}
