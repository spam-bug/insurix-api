<?php

declare(strict_types=1);

namespace App\Enums;

/** What a spreadsheet brings in. */
enum ImportKind: string
{
    case Clients = 'clients';
    case Leads = 'leads';
}
