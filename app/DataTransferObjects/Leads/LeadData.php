<?php

namespace App\DataTransferObjects\Leads;

use App\Enums\LeadInterest;
use App\Enums\LeadSource;

class LeadData
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $email,
        public readonly ?string $phone,
        public readonly ?string $address,
        public readonly LeadSource $source,
        public readonly LeadInterest $interest,
        public readonly ?string $notes,
    ) {}

    /**
     * @return array{name: string, email: string|null, phone: string|null, address: string|null, source: LeadSource, interest: LeadInterest, notes: string|null}
     */
    public function toAttributes(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'source' => $this->source,
            'interest' => $this->interest,
            'notes' => $this->notes,
        ];
    }
}
