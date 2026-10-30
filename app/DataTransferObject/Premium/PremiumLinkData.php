<?php

declare(strict_types=1);

namespace App\DataTransferObject\Premium;

use App\DataTransferObject\Data;
use Carbon\CarbonImmutable;

class PremiumLinkData extends Data
{
    public function __construct(
        public readonly ?int $days,
        public readonly int $maxClaims = 1,
        public readonly ?CarbonImmutable $validUntil = null,
        public readonly ?string $email = null,
        public readonly ?string $note = null,
    ) {}
}
