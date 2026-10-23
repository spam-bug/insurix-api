<?php

declare(strict_types=1);

namespace App\DataTransferObject\Renewals;

use App\DataTransferObject\Data;
use App\Enums\RenewalOutcome;
use Carbon\CarbonImmutable;

class RenewalData extends Data
{
    public function __construct(
        public readonly CarbonImmutable $dueOn,
        public readonly RenewalOutcome $outcome,
        public readonly ?CarbonImmutable $nextRenewalDate = null,
        public readonly ?string $notes = null,
    ) {}
}
