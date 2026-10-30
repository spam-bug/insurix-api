<?php

declare(strict_types=1);

namespace App\DataTransferObject\Forms;

use App\DataTransferObject\Data;
use App\Enums\FormTemplate;

class FormData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly ?FormTemplate $template = null,
    ) {}
}
