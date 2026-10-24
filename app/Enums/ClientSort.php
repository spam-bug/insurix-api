<?php

declare(strict_types=1);

namespace App\Enums;

enum ClientSort: string
{
    case Latest = 'latest';
    case Name = 'name';

    /** Largest annual premium in force first. */
    case Premium = 'premium';

    /** Longest without a completed follow-up first, never contacted at the top: who to call next. */
    case LastContact = 'last_contact';
}
