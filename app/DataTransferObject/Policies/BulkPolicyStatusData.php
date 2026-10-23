<?php

declare(strict_types=1);

namespace App\DataTransferObject\Policies;

use App\DataTransferObject\Data;
use App\Enums\PolicyStatus;

class BulkPolicyStatusData extends Data
{
    public function __construct(
        /** @var array<int, string> */
        public readonly array $ids,
        public readonly PolicyStatus $status,
    ) {}
}
