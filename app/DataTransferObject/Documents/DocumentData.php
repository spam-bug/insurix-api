<?php

declare(strict_types=1);

namespace App\DataTransferObject\Documents;

use App\DataTransferObject\Data;
use App\Enums\DocumentCategory;

class DocumentData extends Data
{
    public function __construct(
        public readonly DocumentCategory $category,
        /** An upload may leave the name out and be named after its file. */
        public readonly ?string $name = null,
        /** Null keeps the document unassigned, in the Documents inbox. */
        public readonly ?string $clientId = null,
        public readonly ?string $policyId = null,
        public readonly ?string $notes = null,
    ) {}
}
