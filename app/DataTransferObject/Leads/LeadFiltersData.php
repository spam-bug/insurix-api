<?php

declare(strict_types=1);

namespace App\DataTransferObject\Leads;

use App\Enums\LeadInterest;
use App\Enums\LeadSort;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;

class LeadFiltersData
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?LeadStatus $status = null,
        /** Only leads still in the pipeline. Ignored when a status is picked. */
        public readonly bool $open = false,
        public readonly ?LeadInterest $interest = null,
        public readonly ?LeadSource $source = null,
        public readonly LeadSort $sort = LeadSort::Latest,
        public readonly int $perPage = 10,
    ) {}
}
