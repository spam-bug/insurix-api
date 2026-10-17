<?php

namespace App\DataTransferObjects\FollowUps;

use App\Enums\FollowUpWindow;

class FollowUpFiltersData
{
    public function __construct(
        public readonly FollowUpWindow $window = FollowUpWindow::Open,
        public readonly ?int $leadId = null,
        public readonly int $perPage = 20,
    ) {}
}
