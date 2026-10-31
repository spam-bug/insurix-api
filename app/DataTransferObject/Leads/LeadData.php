<?php

declare(strict_types=1);

namespace App\DataTransferObject\Leads;

use App\DataTransferObject\Data;
use App\Enums\ContactChannel;
use App\Enums\LeadInterest;
use App\Enums\LeadSource;

class LeadData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $email = null,
        public readonly ?string $phone = null,
        public readonly ?ContactChannel $preferredChannel = null,
        public readonly ?string $messengerUsername = null,
        public readonly ?string $telegramUsername = null,
        public readonly ?string $address = null,
        public readonly LeadSource $source = LeadSource::Manual,
        public readonly ?LeadInterest $interest = null,
        public readonly ?string $notes = null,
    ) {}
}
