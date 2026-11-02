<?php

declare(strict_types=1);

namespace App\DataTransferObject\Staff;

use App\DataTransferObject\Data;
use App\Enums\Plan;

class StaffWorkspaceFilters extends Data
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?Plan $plan = null,
        public readonly bool $deleted = false,
        public readonly int $page = 1,
    ) {}
}
