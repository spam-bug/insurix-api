<?php

declare(strict_types=1);

namespace App\DataTransferObject\Auth;

use App\DataTransferObject\Data;

class RegisterUserData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
    ) {}
}
