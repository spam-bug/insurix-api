<?php

namespace App\DataTransferObjects\Documents;

use App\Enums\DocumentCategory;

class DocumentFiltersData
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?DocumentCategory $category = null,
        public readonly ?int $clientId = null,
        public readonly ?int $policyId = null,
        /** Only documents that have expired or expire soon, soonest first. */
        public readonly bool $expiring = false,
        public readonly int $perPage = 10,
    ) {}
}
