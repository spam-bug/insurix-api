<?php

declare(strict_types=1);

namespace App\DataTransferObject\Staff;

use App\DataTransferObject\Data;
use App\Enums\StaffRole;

class StaffData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
        public readonly StaffRole $role,
    ) {}
}
