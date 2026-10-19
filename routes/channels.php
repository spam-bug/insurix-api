<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

// Each agent listens on their own private channel for changes made outside their browser:
// shareable form submissions and the nightly jobs.
Broadcast::channel('agents.{agentId}', function (User $user, string $agentId) {
    return $user->id === $agentId;
});
