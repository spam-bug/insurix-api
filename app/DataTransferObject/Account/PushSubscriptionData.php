<?php

declare(strict_types=1);

namespace App\DataTransferObject\Account;

use App\DataTransferObject\Data;

class PushSubscriptionData extends Data
{
    public function __construct(
        public readonly string $endpoint,
        public readonly string $publicKey,
        public readonly string $authToken,
        public readonly ?string $contentEncoding = null,
    ) {}
}
