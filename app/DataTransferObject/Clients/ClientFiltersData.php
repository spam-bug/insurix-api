<?php

declare(strict_types=1);

namespace App\DataTransferObject\Clients;

use App\DataTransferObject\Data;
use App\Enums\ClientSort;

class ClientFiltersData extends Data
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ClientSort $sort = ClientSort::Latest,
        public readonly int $perPage = 10,
        public readonly int $page = 1,
    ) {}
}
