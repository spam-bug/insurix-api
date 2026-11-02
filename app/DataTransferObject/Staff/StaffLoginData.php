<?php

declare(strict_types=1);

namespace App\DataTransferObject\Staff;

use App\DataTransferObject\Data;

class StaffLoginData extends Data
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
    ) {}
}
