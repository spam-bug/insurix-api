<?php

declare(strict_types=1);

namespace App\DataTransferObject\Auth;

use App\DataTransferObject\Data;

class PasswordResetData extends Data
{
    public function __construct(
        public readonly string $token,
        public readonly string $email,
        public readonly string $password,
    ) {}
}
