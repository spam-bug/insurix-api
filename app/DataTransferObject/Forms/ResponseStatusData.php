<?php

declare(strict_types=1);

namespace App\DataTransferObject\Forms;

use App\DataTransferObject\Data;
use App\Enums\FormResponseStatus;

class ResponseStatusData extends Data
{
    /**
     * @param  list<string>  $responseIds
     */
    public function __construct(
        public readonly array $responseIds,
        public readonly FormResponseStatus $status,
    ) {}
}
