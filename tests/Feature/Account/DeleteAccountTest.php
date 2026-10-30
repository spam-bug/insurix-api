<?php

declare(strict_types=1);

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\WorkspaceDeletedNotification;
use Illuminate\Support\Facades\Notification;

it('deletes the account and every workspace it owns, telling teammates', function () {
    Notification::fake();
    $agent = User::factory()->create(['password' => 'secret-password']);
    $teammate = User::factory()->create();
    $owned = workspaceOf($agent);
    $owned->users()->attach($teammate, ['role' => WorkspaceRole::Member->value]);
    $helping = workspaceOf($teammate);
    $helping->users()->attach($agent, ['role' => WorkspaceRole::Member->value]);

    $this->actingAs($agent)->getJson('/api/v1/workspaces')->assertJsonPath('data.0.members_count', 2);
    $this->deleteJson('/api/v1/account', ['password' => 'wrong'])->assertUnprocessable()->assertJsonValidationErrors('password');
    $this->deleteJson('/api/v1/account', ['password' => 'secret-password'])->assertNoContent();

    expect(User::find($agent->id))->toBeNull()
        ->and(Workspace::withTrashed()->find($owned->id))->toBeNull()
        ->and($helping->users()->whereKey($agent->id)->exists())->toBeFalse()
        ->and($helping->fresh())->not->toBeNull();
    Notification::assertSentTo($teammate, WorkspaceDeletedNotification::class, fn ($notification) => $notification->restorableUntil === null);
    // actingAs() keeps the user on the guard between test requests; a real browser's session is gone.
    $this->app['auth']->forgetGuards();
    $this->getJson('/api/v1/auth/user')->assertUnauthorized();
});
