<?php

declare(strict_types=1);

namespace App\DataTransferObject\Forms;

use App\DataTransferObject\Data;
use App\Enums\ImportKind;

class ImportResponsesData extends Data
{
    /**
     * @param  list<string>  $responseIds
     */
    public function __construct(
        public readonly ImportKind $kind,
        public readonly array $responseIds,
    ) {}
}
