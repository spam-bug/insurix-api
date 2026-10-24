<?php

declare(strict_types=1);

namespace App\DataTransferObject\Workspaces;

use App\DataTransferObject\Data;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;

class WorkspaceChangeData extends Data
{
    /**
     * @param  string|null  $recordId  the record that changed; null for bulk changes
     * @param  int  $count  how many records changed, for bulk changes
     * @param  string|null  $message  a note worth showing everyone, such as a new form lead
     */
    public function __construct(
        public readonly ChangeTopic $topic,
        public readonly ChangeAction $action,
        public readonly ?string $recordId = null,
        public readonly ?string $recordName = null,
        public readonly int $count = 1,
        public readonly ?string $message = null,
    ) {}
}
