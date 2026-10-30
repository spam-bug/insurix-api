<?php

declare(strict_types=1);

namespace App\DataTransferObject\Forms;

use App\DataTransferObject\Data;

class FormResponseData extends Data
{
    /**
     * @param  array<string, mixed>  $answers  keyed by field id
     */
    public function __construct(
        public readonly array $answers = [],
        /** A honeypot: people never see this field, simple bots fill it in. */
        public readonly ?string $website = null,
    ) {}
}
