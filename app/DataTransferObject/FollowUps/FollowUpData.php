<?php

declare(strict_types=1);

namespace App\DataTransferObject\FollowUps;

use App\DataTransferObject\Data;
use App\Enums\FollowUpType;
use Carbon\CarbonImmutable;

class FollowUpData extends Data
{
    public function __construct(
        public readonly FollowUpType $type,
        public readonly string $title,
        public readonly CarbonImmutable $dueAt,
        public readonly ?string $notes = null,
    ) {}
}
