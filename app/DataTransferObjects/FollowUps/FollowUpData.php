<?php

declare(strict_types=1);

namespace App\DataTransferObjects\FollowUps;

use App\Enums\FollowUpType;
use Carbon\CarbonImmutable;

class FollowUpData
{
    public function __construct(
        public readonly FollowUpType $type,
        public readonly string $title,
        public readonly CarbonImmutable $dueAt,
        public readonly ?string $notes,
    ) {}

    /**
     * @return array{type: FollowUpType, title: string, due_at: CarbonImmutable, notes: string|null}
     */
    public function toAttributes(): array
    {
        return [
            'type' => $this->type,
            'title' => $this->title,
            'due_at' => $this->dueAt,
            'notes' => $this->notes,
        ];
    }
}
