<?php

declare(strict_types=1);

use App\Enums\NotificationType;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

beforeEach(fn () => Carbon::setTestNow('2026-10-17 02:00:00'));

function notify(User $user, NotificationType $type = NotificationType::FormLead, array $attributes = [], ?Workspace $workspace = null): string
{
    $id = (string) Str::uuid();
    $user->notifications()->create([
        'id' => $id,
        'type' => $type->value,
        'workspace_id' => ($workspace ?? workspaceOf($user))->id,
        'data' => ['title' => 'New lead from your form', 'body' => 'Juan Cruz filled in your form.', 'actor' => null, 'subject' => ['kind' => 'lead', 'id' => 'lead-1', 'name' => 'Juan Cruz']],
        ...$attributes,
    ]);

    return $id;
}

it('lists the user’s notifications for the workspace, newest first', function () {
    $agent = User::factory()->create();
    $older = notify($agent, attributes: ['created_at' => now()->subDay(), 'read_at' => now()]);
    $newer = notify($agent, NotificationType::RenewalComingUp);
    notify(User::factory()->create());
    notify($agent, workspace: Workspace::factory()->create());

    $this->actingAs($agent)->getJson(ws($agent).'/notifications')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $newer)
        ->assertJsonPath('data.0.type', ['value' => 'renewal_coming_up', 'label' => 'Renewal'])
        ->assertJsonPath('data.0.read_at', null)
        ->assertJsonPath('data.1.id', $older)
        ->assertJsonPath('data.1.title', 'New lead from your form')
        ->assertJsonPath('data.1.subject.name', 'Juan Cruz')
        ->assertJsonPath('meta.total', 2);
});

it('filters to unread notifications, or one kind', function () {
    $agent = User::factory()->create();
    notify($agent, attributes: ['read_at' => now()]);
    $unread = notify($agent);
    $renewal = notify($agent, NotificationType::RenewalComingUp, ['read_at' => now()]);

    $this->actingAs($agent)->getJson(ws($agent).'/notifications?unread=1')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $unread);
    $this->getJson(ws($agent).'/notifications?type=renewal_coming_up')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $renewal);
    $this->getJson(ws($agent).'/notifications?type=birthday')->assertJsonValidationErrors('type');
});

it('counts unread notifications for the bell, and keeps the count fresh as they arrive and are read', function () {
    $agent = User::factory()->create();
    notify($agent);
    notify($agent, attributes: ['read_at' => now()]);
    notify($agent, workspace: Workspace::factory()->create());

    $this->actingAs($agent)->getJson(ws($agent).'/notifications/unread-count')->assertExactJson(['data' => ['count' => 1]]);

    $id = notify($agent);
    cache()->tags("notifications:{$agent->id}")->flush();
    $this->getJson(ws($agent).'/notifications/unread-count')->assertExactJson(['data' => ['count' => 2]]);

    $this->patchJson(ws($agent)."/notifications/{$id}")->assertOk()->assertJsonPath('data.read_at', now()->toIso8601String());
    $this->getJson(ws($agent).'/notifications/unread-count')->assertExactJson(['data' => ['count' => 1]]);
});

it('only lets the recipient mark a notification read, and only from its workspace', function () {
    $agent = User::factory()->create();
    $teammate = User::factory()->create();
    workspaceOf($agent)->users()->attach($teammate, ['role' => 'member']);
    $teammates = notify($teammate, workspace: workspaceOf($agent));
    $elsewhere = notify($agent, workspace: Workspace::factory()->create());

    $this->actingAs($agent)->patchJson(ws($agent)."/notifications/{$teammates}")->assertForbidden();
    $this->patchJson(ws($agent)."/notifications/{$elsewhere}")->assertNotFound();
});

it('marks all of the user’s notifications in the workspace read', function () {
    $agent = User::factory()->create();
    notify($agent);
    notify($agent);
    $otherWorkspace = notify($agent, workspace: Workspace::factory()->create());

    $this->actingAs($agent)->postJson(ws($agent).'/notifications/read-all')->assertExactJson(['data' => ['marked' => 2]]);

    $this->getJson(ws($agent).'/notifications/unread-count')->assertExactJson(['data' => ['count' => 0]]);
    expect($agent->notifications()->find($otherWorkspace)->read_at)->toBeNull();
});

it('needs a member of the workspace', function () {
    $agent = User::factory()->create();

    $this->getJson(ws($agent).'/notifications')->assertUnauthorized();
    $this->actingAs(User::factory()->create())->getJson(ws($agent).'/notifications')->assertNotFound();
});

it('deletes notifications older than 90 days', function () {
    $agent = User::factory()->create();
    notify($agent, attributes: ['created_at' => now()->subDays(91)]);
    $kept = notify($agent, attributes: ['created_at' => now()->subDays(89)]);

    $this->artisan('notifications:prune')->assertSuccessful();

    expect($agent->notifications()->pluck('id')->all())->toBe([$kept]);
});

it('reads notifications and the count back from a cache that serializes, like Redis', function () {
    config(['cache.stores.array.serialize' => true]);
    cache()->forgetDriver('array');
    $agent = User::factory()->create();
    notify($agent);

    foreach ([1, 2] as $attempt) {
        $this->actingAs($agent)->getJson(ws($agent).'/notifications')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson(ws($agent).'/notifications/unread-count')->assertExactJson(['data' => ['count' => 1]]);
    }
});
