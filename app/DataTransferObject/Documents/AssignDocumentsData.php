<?php

declare(strict_types=1);

namespace App\DataTransferObject\Documents;

use App\DataTransferObject\Data;

class AssignDocumentsData extends Data
{
    public function __construct(
        /** @var array<int, string> */
        public readonly array $ids,
        public readonly string $clientId,
    ) {}
}
