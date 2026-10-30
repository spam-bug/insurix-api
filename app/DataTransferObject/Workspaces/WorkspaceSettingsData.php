<?php

declare(strict_types=1);

namespace App\DataTransferObject\Workspaces;

use App\DataTransferObject\Data;

class WorkspaceSettingsData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly string $timezone,
    ) {}
}
