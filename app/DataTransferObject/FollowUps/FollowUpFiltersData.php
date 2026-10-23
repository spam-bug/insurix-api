<?php

declare(strict_types=1);

namespace App\DataTransferObject\FollowUps;

use App\DataTransferObject\Data;
use App\Enums\FollowUpWindow;

class FollowUpFiltersData extends Data
{
    public function __construct(
        public readonly FollowUpWindow $window = FollowUpWindow::Open,
        public readonly ?string $leadId = null,
        public readonly int $perPage = 10,
        public readonly int $page = 1,
    ) {}
}
