<?php

declare(strict_types=1);

namespace App\DataTransferObject\Staff;

use App\DataTransferObject\Data;

class StaffSearchFilters extends Data
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly int $page = 1,
    ) {}
}
