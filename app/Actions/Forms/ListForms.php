<?php

declare(strict_types=1);

namespace App\Actions\Forms;

use App\Models\Form;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class ListForms
{
    /**
     * The default form first, then the newest. A workspace has a handful, so no paging.
     *
     * @return Collection<int, Form>
     */
    public function handle(Workspace $workspace): Collection
    {
        return Cache::tags("workspace:{$workspace->id}")->remember("forms:{$workspace->id}:index", now()->addMinutes(5), fn () => $workspace->forms()
            ->withCount('responses')
            ->orderByDesc('is_default')
            ->latest()
            ->get());
    }
}
