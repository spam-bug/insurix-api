<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Policies;

use App\Enums\PaymentFrequency;
use App\Enums\PolicyStatus;
use App\Enums\PolicyType;
use App\Enums\RenewalTerm;
use Carbon\CarbonImmutable;

class PolicyData
{
    public function __construct(
        public readonly ?string $productId,
        public readonly string $productName,
        public readonly ?string $policyNumber,
        public readonly PolicyType $type,
        public readonly PolicyStatus $status,
        public readonly string $premium,
        public readonly ?string $coverageAmount,
        public readonly PaymentFrequency $paymentFrequency,
        public readonly string $commissionRate,
        public readonly ?CarbonImmutable $effectiveDate,
        public readonly ?RenewalTerm $renewalTerm,
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
            'product_id' => $this->productId,
            'product_name' => $this->productName,
            'policy_number' => $this->policyNumber,
            'type' => $this->type,
            'status' => $this->status,
            'premium' => $this->premium,
            'coverage_amount' => $this->coverageAmount,
            'payment_frequency' => $this->paymentFrequency,
            'commission_rate' => $this->commissionRate,
            'effective_date' => $this->effectiveDate,
            'renewal_term' => $this->renewalTerm,
            'renewal_date' => $this->renewalDate,
            'commission_received_at' => $this->commissionReceivedAt,
            'notes' => $this->notes,
        ];
    }
}
