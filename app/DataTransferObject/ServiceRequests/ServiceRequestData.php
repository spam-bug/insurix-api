<?php

declare(strict_types=1);

namespace App\DataTransferObject\ServiceRequests;

use App\DataTransferObject\Data;
use App\Enums\ServiceRequestStatus;
use App\Enums\ServiceRequestType;
use Carbon\CarbonImmutable;

class ServiceRequestData extends Data
{
    public function __construct(
        public readonly ServiceRequestType $type,
        public readonly string $title,
        public readonly ServiceRequestStatus $status = ServiceRequestStatus::Open,
        public readonly ?string $details = null,
        public readonly ?string $policyId = null,
        public readonly ?CarbonImmutable $dueOn = null,
        public readonly ?float $claimAmount = null,
        public readonly ?string $insurerReference = null,
    ) {}
}
