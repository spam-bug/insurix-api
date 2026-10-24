<?php

declare(strict_types=1);

namespace App\DataTransferObject\Workspaces;

use App\DataTransferObject\Data;
use App\Enums\WorkspaceRole;

class InvitationData extends Data
{
    public function __construct(
        public readonly string $email,
        public readonly WorkspaceRole $role = WorkspaceRole::Member,
    ) {}
}
