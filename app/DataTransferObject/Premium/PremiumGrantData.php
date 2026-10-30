<?php

declare(strict_types=1);

namespace App\DataTransferObject\Premium;

use App\DataTransferObject\Data;

class PremiumGrantData extends Data
{
    public function __construct(
        public readonly ?int $days,
        public readonly ?string $note = null,
    ) {}
}
