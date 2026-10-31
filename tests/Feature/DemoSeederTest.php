<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\DemoSeeder;

it('seeds a demo workspace with leads, clients, policies and follow-ups', function () {
    $this->seed(DemoSeeder::class);

    $workspace = User::where('email', 'demo@insurix.test')->sole()->workspaces()->sole();

    expect($workspace->name)->toBe('Santos Insurance Advisory')
        ->and($workspace->leads()->count())->toBe(29)
        ->and($workspace->clients()->count())->toBe(10)
        ->and($workspace->policies()->count())->toBe(11)
        ->and($workspace->followUps()->whereNull('completed_at')->count())->toBe(7)
        ->and($workspace->forms()->count())->toBe(2);
});
