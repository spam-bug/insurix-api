<?php

declare(strict_types=1);

namespace App\DataTransferObject\Premium;

use App\DataTransferObject\Data;

class ClaimPremiumLinkData extends Data
{
    public function __construct(
        public readonly ?string $workspaceId = null,
    ) {}
}
