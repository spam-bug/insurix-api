<?php

declare(strict_types=1);

namespace App\Actions\Automations;

use App\Models\Automation;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class ListAutomations
{
    /**
     * Every automation in the workspace, oldest first (the order they run in), with how often
     * and when each last ran.
     *
     * @return Collection<int, Automation>
     */
    public function handle(Workspace $workspace): Collection
    {
        return Cache::tags("workspace:{$workspace->id}")->remember("automations:{$workspace->id}:index", now()->addMinutes(10), fn () => $workspace->automations()
            ->withCount('runs')
            ->withMax('runs', 'created_at')
            ->oldest()
            ->oldest('id')
            ->get());
    }
}
