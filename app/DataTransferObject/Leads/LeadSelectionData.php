<?php

declare(strict_types=1);

namespace App\DataTransferObject\Leads;

use App\DataTransferObject\Data;

class LeadSelectionData extends Data
{
    public function __construct(
        /** @var array<int, string> */
        public readonly array $ids,
    ) {}
}
