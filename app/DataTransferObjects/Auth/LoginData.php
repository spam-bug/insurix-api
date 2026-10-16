<?php

namespace App\DataTransferObjects\Auth;

class LoginData
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
        public readonly string $ipAddress,
    ) {}
}
