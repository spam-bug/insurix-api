<?php

declare(strict_types=1);

namespace App\DataTransferObject\Policies;

use App\DataTransferObject\Data;
use App\Enums\PaymentFrequency;
use App\Enums\PolicyStatus;
use App\Enums\PolicyType;
use App\Enums\RenewalTerm;
use Carbon\CarbonImmutable;

class PolicyData extends Data
{
    public function __construct(
        public readonly PolicyStatus $status,
        public readonly string $premium,
        public readonly PaymentFrequency $paymentFrequency,
        public readonly string $commissionRate,
        public readonly ?string $productId = null,
        public readonly ?string $productName = null,
        public readonly ?string $policyNumber = null,
        public readonly ?PolicyType $type = null,
        public readonly ?string $coverageAmount = null,
        public readonly ?CarbonImmutable $effectiveDate = null,
        public readonly ?RenewalTerm $renewalTerm = null,
        public readonly ?CarbonImmutable $renewalDate = null,
        public readonly ?CarbonImmutable $commissionReceivedAt = null,
        public readonly ?string $notes = null,
    ) {}
}
