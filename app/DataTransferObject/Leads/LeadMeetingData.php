<?php

declare(strict_types=1);

namespace App\DataTransferObject\Leads;

use Carbon\CarbonImmutable;

class LeadMeetingData
{
    public function __construct(
        public readonly CarbonImmutable $scheduledAt,
        public readonly ?string $notes,
    ) {}
}
