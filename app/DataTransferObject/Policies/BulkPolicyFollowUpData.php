<?php

declare(strict_types=1);

namespace App\DataTransferObject\Policies;

use App\DataTransferObject\Data;
use App\Enums\FollowUpType;
use Carbon\CarbonImmutable;

class BulkPolicyFollowUpData extends Data
{
    public function __construct(
        /** @var array<int, string> */
        public readonly array $ids,
        public readonly FollowUpType $type,
        public readonly string $title,
        public readonly CarbonImmutable $dueAt,
        public readonly ?string $notes = null,
    ) {}
}
