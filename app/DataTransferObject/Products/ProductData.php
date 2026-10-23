<?php

declare(strict_types=1);

namespace App\DataTransferObject\Products;

use App\DataTransferObject\Data;
use App\Enums\PaymentFrequency;
use App\Enums\PolicyType;

class ProductData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly PolicyType $type,
        public readonly string $commissionRate,
        public readonly ?string $description = null,
        public readonly ?PaymentFrequency $paymentFrequency = null,
        public readonly bool $isActive = true,
    ) {}
}
