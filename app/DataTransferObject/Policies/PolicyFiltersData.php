<?php

declare(strict_types=1);

namespace App\DataTransferObject\Policies;

use App\Enums\PolicyStatus;

class PolicyFiltersData
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?PolicyStatus $status = null,
        public readonly ?string $clientId = null,
        public readonly ?string $productId = null,
        public readonly int $perPage = 10,
    ) {}
}
