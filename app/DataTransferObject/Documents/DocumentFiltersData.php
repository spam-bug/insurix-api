<?php

declare(strict_types=1);

namespace App\DataTransferObject\Documents;

use App\DataTransferObject\Data;
use App\Enums\DocumentCategory;

class DocumentFiltersData extends Data
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?DocumentCategory $category = null,
        public readonly ?string $clientId = null,
        /** Only files still waiting in the inbox for a client. */
        public readonly bool $unassigned = false,
        public readonly ?string $policyId = null,
        public readonly int $perPage = 10,
        public readonly int $page = 1,
    ) {}
}
