<?php

declare(strict_types=1);

namespace App\Actions\Staff;

use App\Models\PremiumLink;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListPremiumLinksForStaff
{
    /**
     * Premium links, newest first, with how many times each was claimed.
     *
     * @return LengthAwarePaginator<int, PremiumLink>
     */
    public function handle(int $page): LengthAwarePaginator
    {
        return PremiumLink::query()
            ->withCount('grants')
            ->latest()
            ->paginate(25, page: $page);
    }
}
