<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Import;
use App\Models\User;
use App\Policies\Concerns\ChecksMembership;

class ImportPolicy
{
    use ChecksMembership;

    public function view(User $user, Import $import): bool
    {
        return $this->isMember($user, $import->workspace_id);
    }

    /** Anyone in the workspace can carry on or cancel an import a teammate started. */
    public function update(User $user, Import $import): bool
    {
        return $this->isMember($user, $import->workspace_id);
    }
}
