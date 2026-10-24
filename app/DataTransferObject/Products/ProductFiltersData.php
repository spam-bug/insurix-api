<?php

declare(strict_types=1);

namespace App\DataTransferObject\Products;

use App\DataTransferObject\Data;
use App\Enums\PolicyType;
use App\Enums\ProductSort;

class ProductFiltersData extends Data
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?PolicyType $type = null,
        /** Null lists active and inactive products alike. */
        public readonly ?bool $active = null,
        public readonly ProductSort $sort = ProductSort::Name,
        public readonly int $perPage = 10,
        public readonly int $page = 1,
    ) {}
}
