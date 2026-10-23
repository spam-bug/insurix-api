<?php

declare(strict_types=1);

namespace App\DataTransferObject\Leads;

use App\DataTransferObject\Data;
use App\Enums\LeadStatus;

class BulkLeadStatusData extends Data
{
    public function __construct(
        /** @var array<int, string> */
        public readonly array $ids,
        public readonly LeadStatus $status,
    ) {}
}
