<?php

declare(strict_types=1);

namespace App\DataTransferObject\Leads;

use App\DataTransferObject\Data;
use Carbon\CarbonImmutable;

class LeadMeetingData extends Data
{
    public function __construct(
        public readonly CarbonImmutable $scheduledAt,
        public readonly ?string $notes = null,
    ) {}
}
