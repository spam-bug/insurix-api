<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Models\Workspace;

class MeasureStorage
{
    /**
     * How much of the workspace's document storage is used, and its limit, in bytes.
     *
     * @return array{used: int, limit: int}
     */
    public function handle(Workspace $workspace): array
    {
        return [
            'used' => (int) $workspace->documents()->sum('size'),
            'limit' => config('insurix.storage_limit_bytes'),
        ];
    }
}
