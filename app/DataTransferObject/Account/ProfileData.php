<?php

declare(strict_types=1);

namespace App\DataTransferObject\Account;

use App\DataTransferObject\Data;

class ProfileData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $timezone,
        public readonly ?string $phone = null,
    ) {}
}
