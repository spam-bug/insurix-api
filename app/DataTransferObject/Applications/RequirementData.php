<?php

declare(strict_types=1);

namespace App\DataTransferObject\Applications;

use App\DataTransferObject\Data;

class RequirementData extends Data
{
    public function __construct(
        public readonly bool $received,
        public readonly ?string $documentId = null,
    ) {}
}
