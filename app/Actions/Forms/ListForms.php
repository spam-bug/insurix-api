<?php

declare(strict_types=1);

namespace App\Actions\Forms;

use App\Enums\FormResponseStatus;
use App\Models\Form;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class ListForms
{
    /**
     * The default form first, then the newest, with how many responses each has and how many are
     * still new. A workspace has a handful, so no paging.
     *
     * @return Collection<int, Form>
     */
    public function handle(Workspace $workspace): Collection
    {
        return Cache::tags("workspace:{$workspace->id}")->remember("forms:{$workspace->id}:index", now()->addMinutes(5), fn () => $workspace->forms()
            ->withCount(['responses', 'responses as new_responses_count' => fn ($query) => $query->where('status', FormResponseStatus::New)])
            ->orderByDesc('is_default')
            ->latest()
            ->get());
    }
}
