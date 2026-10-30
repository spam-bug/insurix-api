<?php

declare(strict_types=1);

namespace App\DataTransferObject\Forms;

use App\DataTransferObject\Data;
use App\Enums\FormResponseStatus;

class FormResponseFiltersData extends Data
{
    public function __construct(
        public readonly ?string $formId = null,
        public readonly ?string $leadId = null,
        public readonly ?string $clientId = null,
        public readonly ?FormResponseStatus $status = null,
        public readonly int $page = 1,
    ) {}
}
