<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Documents;

use App\Enums\DocumentCategory;

class DocumentFiltersData
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?DocumentCategory $category = null,
        public readonly ?string $clientId = null,
        public readonly ?string $policyId = null,
        public readonly int $perPage = 10,
    ) {}
}
