<?php

declare(strict_types=1);

namespace App\DataTransferObject\Clients;

use App\DataTransferObject\Data;

class ClientFiltersData extends Data
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly int $perPage = 10,
        public readonly int $page = 1,
    ) {}
}
