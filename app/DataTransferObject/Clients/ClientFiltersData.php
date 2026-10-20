<?php

declare(strict_types=1);

namespace App\DataTransferObject\Clients;

class ClientFiltersData
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly int $perPage = 10,
    ) {}
}
