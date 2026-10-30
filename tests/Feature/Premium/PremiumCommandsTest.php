<?php

declare(strict_types=1);

use App\Models\PremiumLink;
use App\Models\User;

it('makes a premium link and prints its claim page', function () {
    $this->artisan('premium:create-link', ['--years' => 1, '--claims' => 10, '--note' => 'Beta testers'])
        ->expectsOutputToContain(config('app.frontend_url').'/premium/')
        ->assertSuccessful();

    expect(PremiumLink::sole())->days->toBe(365)->max_claims->toBe(10)->note->toBe('Beta testers');
});

it('needs exactly one duration', function () {
    $this->artisan('premium:create-link', ['--days' => 30, '--forever' => true])->assertExitCode(2);
});

it('grants Premium to the account’s own workspaces and takes it back', function () {
    $owner = User::factory()->create(['email' => 'beta@example.com']);

    $this->artisan('premium:grant', ['email' => 'beta@example.com', '--forever' => true])->assertSuccessful();
    expect(workspaceOf($owner)->currentPlan()->value)->toBe('premium')
        ->and(workspaceOf($owner)->plan_ends_at)->toBeNull();

    $this->artisan('premium:grant', ['email' => 'beta@example.com', '--revoke' => true])->assertSuccessful();
    expect(workspaceOf($owner)->currentPlan()->value)->toBe('free');
});

it('fails for an account that owns no workspace', function () {
    $this->artisan('premium:grant', ['email' => 'nobody@example.com', '--days' => 30])->assertFailed();
});
