<?php

declare(strict_types=1);

namespace App\DataTransferObject\Workspaces;

use App\DataTransferObject\Data;

class OwnershipData extends Data
{
    public function __construct(
        public readonly string $memberId,
    ) {}
}
