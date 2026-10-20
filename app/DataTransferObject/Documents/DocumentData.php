<?php

declare(strict_types=1);

namespace App\DataTransferObject\Documents;

use App\Enums\DocumentCategory;

class DocumentData
{
    public function __construct(
        public readonly ?string $name,
        public readonly DocumentCategory $category,
        public readonly ?string $policyId,
        public readonly ?string $notes,
    ) {}

    /**
     * The details the agent can edit. The name is left out when none was given, so an upload
     * can name the document after its file.
     *
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            ...($this->name === null ? [] : ['name' => $this->name]),
            'category' => $this->category,
            'policy_id' => $this->policyId,
            'notes' => $this->notes,
        ];
    }
}
