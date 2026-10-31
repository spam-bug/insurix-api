<?php

declare(strict_types=1);

namespace App\DataTransferObject\Automations;

use App\DataTransferObject\Data;
use App\Enums\AutomationMatch;
use App\Enums\AutomationTrigger;
use App\Enums\LeadStatus;

class AutomationData extends Data
{
    /**
     * @param  list<array{field: string, operator: string, value?: string|int}>  $conditions
     * @param  list<array<string, mixed>>  $actions
     */
    public function __construct(
        public readonly string $name,
        public readonly AutomationTrigger $trigger,
        public readonly AutomationMatch $match,
        public readonly array $conditions,
        public readonly array $actions,
        public readonly ?LeadStatus $triggerStage = null,
        public readonly ?int $idleDays = null,
        public readonly bool $enabled = true,
    ) {}
}
