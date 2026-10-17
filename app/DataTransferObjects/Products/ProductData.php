<?php

namespace App\DataTransferObjects\Products;

use App\Enums\PaymentFrequency;
use App\Enums\PolicyType;

class ProductData
{
    public function __construct(
        public readonly string $name,
        public readonly PolicyType $type,
        public readonly ?string $description,
        public readonly string $commissionRate,
        public readonly ?PaymentFrequency $paymentFrequency,
        public readonly bool $isActive,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'description' => $this->description,
            'commission_rate' => $this->commissionRate,
            'payment_frequency' => $this->paymentFrequency,
            'is_active' => $this->isActive,
        ];
    }
}
