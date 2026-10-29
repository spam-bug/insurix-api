<?php

declare(strict_types=1);

namespace App\Enums;

/** What a spreadsheet row turned out to be, and what the import did with it. */
enum ImportRowStatus: string
{
    case Valid = 'valid';
    case Invalid = 'invalid';
    case Duplicate = 'duplicate';
    case Imported = 'imported';
    case Updated = 'updated';
    case Skipped = 'skipped';
}
