<?php

declare(strict_types=1);

namespace App\DataTransferObject\Applications;

use App\DataTransferObject\Data;
use Carbon\CarbonImmutable;

class ApprovalData extends Data
{
    public function __construct(
        public readonly ?CarbonImmutable $effectiveDate = null,
        public readonly ?string $policyNumber = null,
    ) {}
}
