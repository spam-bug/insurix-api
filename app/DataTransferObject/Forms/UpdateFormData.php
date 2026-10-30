<?php

declare(strict_types=1);

namespace App\DataTransferObject\Forms;

use App\DataTransferObject\Data;

class UpdateFormData extends Data
{
    /**
     * @param  array{sections: list<array<string, mixed>>, settings: array<string, mixed>}|null  $draft
     */
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?array $draft = null,
    ) {}
}
