<?php

namespace App\Enums;

enum LeadSort: string
{
    case Latest = 'latest';
    case Oldest = 'oldest';
    case NameAsc = 'name_asc';
    case NameDesc = 'name_desc';
}
