<?php

declare(strict_types=1);

namespace App\Enums;

enum ProductSort: string
{
    /** Active first, then A–Z. */
    case Name = 'name';

    /** Most policies in force first. */
    case MostSold = 'most_sold';

    /** Largest first-year commission in force first. */
    case TopEarning = 'top_earning';
}
