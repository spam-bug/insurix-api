<?php

declare(strict_types=1);

namespace App\Enums;

/** What kind of business a workspace runs, set by its template and fixed after creation. */
enum WorkspaceType: string
{
    case Insurance = 'insurance';

    public function label(): string
    {
        return match ($this) {
            self::Insurance => 'Insurance',
        };
    }
}
