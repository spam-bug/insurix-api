<?php

declare(strict_types=1);

namespace App\DataTransferObject\ServiceRequests;

use App\DataTransferObject\Data;
use App\Enums\ServiceRequestView;

class ServiceRequestFiltersData extends Data
{
    public function __construct(
        public readonly ?ServiceRequestView $view = null,
        public readonly ?string $search = null,
        public readonly ?string $clientId = null,
        public readonly int $perPage = 20,
        public readonly int $page = 1,
    ) {}
}
