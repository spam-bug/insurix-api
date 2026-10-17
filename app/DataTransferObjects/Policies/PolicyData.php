<?php

namespace App\DataTransferObjects\Policies;

use App\Enums\PaymentFrequency;
use App\Enums\PolicyStatus;
use App\Enums\PolicyType;
use Carbon\CarbonImmutable;

class PolicyData
{
    public function __construct(
        public readonly string $insurer,
        public readonly string $productName,
        public readonly ?string $policyNumber,
        public readonly PolicyType $type,
        public readonly PolicyStatus $status,
        public readonly string $premium,
        public readonly PaymentFrequency $paymentFrequency,
        public readonly string $commissionRate,
        public readonly ?CarbonImmutable $effectiveDate,
        public readonly ?CarbonImmutable $renewalDate,
        public readonly ?CarbonImmutable $commissionReceivedAt,
        public readonly ?string $notes,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            'insurer' => $this->insurer,
            'product_name' => $this->productName,
            'policy_number' => $this->policyNumber,
            'type' => $this->type,
            'status' => $this->status,
            'premium' => $this->premium,
            'payment_frequency' => $this->paymentFrequency,
            'commission_rate' => $this->commissionRate,
            'effective_date' => $this->effectiveDate,
            'renewal_date' => $this->renewalDate,
            'commission_received_at' => $this->commissionReceivedAt,
            'notes' => $this->notes,
        ];
    }
}
