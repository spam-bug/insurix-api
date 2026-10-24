<?php

declare(strict_types=1);

namespace App\DataTransferObject\Insights;

use App\DataTransferObject\Data;
use App\Enums\InsightPeriod;

class InsightReportFiltersData extends Data
{
    public function __construct(
        public readonly InsightPeriod $period = InsightPeriod::Month,
        public readonly int $offset = 0,
    ) {}
}
