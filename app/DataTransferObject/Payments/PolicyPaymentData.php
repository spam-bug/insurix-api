<?php

declare(strict_types=1);

namespace App\DataTransferObject\Payments;

use App\DataTransferObject\Data;
use Carbon\CarbonImmutable;

class PolicyPaymentData extends Data
{
    public function __construct(
        public readonly CarbonImmutable $paidOn,
        public readonly ?string $amount = null,
        public readonly ?string $commissionReceived = null,
        public readonly ?CarbonImmutable $commissionReceivedOn = null,
        public readonly ?string $notes = null,
    ) {}
}
