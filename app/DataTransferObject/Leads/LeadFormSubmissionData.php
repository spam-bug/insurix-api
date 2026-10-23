<?php

declare(strict_types=1);

namespace App\DataTransferObject\Leads;

use App\DataTransferObject\Data;
use App\Enums\LeadInterest;

class LeadFormSubmissionData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $email = null,
        public readonly ?string $phone = null,
        public readonly ?LeadInterest $interest = null,
        public readonly ?string $message = null,
        /** A honeypot: people never see this field, simple bots fill it in. */
        public readonly ?string $website = null,
    ) {}
}
