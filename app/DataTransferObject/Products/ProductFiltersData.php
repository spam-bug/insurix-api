<?php

declare(strict_types=1);

namespace App\DataTransferObject\Products;

use App\Enums\PolicyType;

class ProductFiltersData
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?PolicyType $type = null,
        /** Null lists active and inactive products alike. */
        public readonly ?bool $active = null,
        public readonly int $perPage = 10,
    ) {}
}
