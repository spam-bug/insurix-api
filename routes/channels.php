<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Broadcast;

// Everyone in a workspace listens on its private channel for changes made outside their browser:
// shareable form submissions and the nightly jobs.
Broadcast::channel('workspaces.{workspace}', function (User $user, Workspace $workspace) {
    return $user->can('view', $workspace);
});
