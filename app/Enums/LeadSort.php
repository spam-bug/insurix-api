<?php

declare(strict_types=1);

namespace App\Enums;

enum LeadSort: string
{
    case Latest = 'latest';
    case Oldest = 'oldest';
    case NameAsc = 'name_asc';
    case NameDesc = 'name_desc';

    /** Most likely to become a client first; clients and lost leads, which have no score, last. */
    case Score = 'score';
}
