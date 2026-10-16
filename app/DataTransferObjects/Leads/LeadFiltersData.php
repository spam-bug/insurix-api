<?php

namespace App\DataTransferObjects\Leads;

use App\Enums\LeadInterest;
use App\Enums\LeadStatus;

class LeadFiltersData
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?LeadStatus $status = null,
        public readonly ?LeadInterest $interest = null,
        public readonly int $perPage = 10,
    ) {}
}
