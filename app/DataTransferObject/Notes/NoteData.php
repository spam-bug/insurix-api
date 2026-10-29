<?php

declare(strict_types=1);

namespace App\DataTransferObject\Notes;

use App\DataTransferObject\Data;

class NoteData extends Data
{
    public function __construct(
        public readonly string $body,
        /** At most one of these: what the note is about. */
        public readonly ?string $leadId = null,
        public readonly ?string $clientId = null,
        public readonly ?string $policyId = null,
    ) {}
}
