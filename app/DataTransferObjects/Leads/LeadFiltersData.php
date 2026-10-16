<?php

namespace App\DataTransferObjects\Leads;

use App\Enums\LeadInterest;
use App\Enums\LeadSort;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;

class LeadFiltersData
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?LeadStatus $status = null,
        public readonly ?LeadInterest $interest = null,
        public readonly ?LeadSource $source = null,
        public readonly LeadSort $sort = LeadSort::Latest,
        public readonly int $perPage = 10,
    ) {}
}
