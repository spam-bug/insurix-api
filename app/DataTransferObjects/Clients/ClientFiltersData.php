<?php

namespace App\DataTransferObjects\Clients;

class ClientFiltersData
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly int $perPage = 10,
    ) {}
}
