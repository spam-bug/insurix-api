<?php

declare(strict_types=1);

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(fn () => $this->withHeader('Referer', config('app.frontend_url')))
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/** The workspace the user owns, which every factory-made user has. */
function workspaceOf(User $user): Workspace
{
    return $user->workspaces()->oldest('workspaces.created_at')->firstOrFail();
}

/** The first member of the workspace, for tests that start from a record rather than a user. */
function memberOf(Workspace $workspace): User
{
    $member = $workspace->users()->first();

    if ($member) {
        return $member;
    }

    // Saved directly so the factory doesn't give this user a workspace of their own.
    $owner = tap(User::factory()->make())->save();
    $workspace->users()->attach($owner, ['role' => WorkspaceRole::Owner]);

    return $owner;
}

/** Gives the user's workspace a shareable form token, and returns the user. */
function withLeadForm(User $user, string $token): User
{
    workspaceOf($user)->forceFill(['lead_form_token' => $token])->save();

    return $user;
}

/** The API prefix for the user's own workspace, e.g. ws($agent).'/leads'. */
function ws(User $user): string
{
    return '/api/v1/workspaces/'.workspaceOf($user)->id;
}

/**
 * A browser session for the agent in the sessions table, as the database session driver keeps
 * them. Returns its id.
 */
function storeSession(User $agent, string $userAgent, int $lastActivity = 0): string
{
    $id = Str::random(40);

    DB::table('sessions')->insert([
        'id' => $id,
        'user_id' => $agent->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => $userAgent,
        'payload' => '',
        'last_activity' => $lastActivity ?: now()->timestamp,
    ]);

    return $id;
}

/**
 * Sends the next requests as the given session, so the API treats it as the current device.
 */
function useSession(string $id): void
{
    test()->withCredentials()->withCookie(config('session.cookie'), $id);
}
