<?php

declare(strict_types=1);

namespace App\DataTransferObject\Notes;

use App\DataTransferObject\Data;
use App\Enums\NoteLink;

class NoteFiltersData extends Data
{
    public function __construct(
        public readonly ?NoteLink $link = null,
        public readonly bool $mine = false,
        public readonly ?string $search = null,
        public readonly ?string $leadId = null,
        public readonly ?string $clientId = null,
        public readonly int $perPage = 20,
        public readonly int $page = 1,
    ) {}
}
