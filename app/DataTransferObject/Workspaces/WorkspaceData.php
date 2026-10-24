<?php

declare(strict_types=1);

namespace App\DataTransferObject\Workspaces;

use App\DataTransferObject\Data;
use App\Enums\WorkspaceType;

class WorkspaceData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly WorkspaceType $type,
        public readonly string $timezone,
    ) {}
}
