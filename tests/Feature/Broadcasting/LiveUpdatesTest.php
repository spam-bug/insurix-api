<?php

declare(strict_types=1);

use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
use App\Enums\PaymentFrequency;
use App\Events\WorkspaceDataChanged;
use App\Models\Client;
use App\Models\Lead;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;

function changeBroadcast(callable $matches): void
{
    Event::assertDispatched(WorkspaceDataChanged::class, fn (WorkspaceDataChanged $event) => $matches($event->broadcastWith(), $event));
}

it('tells the workspace when someone fills in its shareable form', function () {
    Event::fake([WorkspaceDataChanged::class]);
    $agent = User::factory()->create();
    workspaceOf($agent)->forceFill(['lead_form_token' => 'form-token'])->save();

    $this->postJson('/api/v1/public/lead-forms/form-token', ['name' => 'Gregoria de Jesus', 'email' => 'gregoria@example.com'])->assertCreated();

    changeBroadcast(fn (array $payload, WorkspaceDataChanged $event) => $event->workspaceId === workspaceOf($agent)->id
        && $payload['topic'] === 'leads'
        && $payload['action'] === 'created'
        && $payload['record']['name'] === 'Gregoria de Jesus'
        && $payload['actor'] === null
        && $payload['message'] === 'Gregoria de Jesus just filled in your form');
});

it('tells the workspace when policy reminders are added to its follow-ups', function () {
    Carbon::setTestNow('2026-10-17 02:00:00');
    Event::fake([WorkspaceDataChanged::class]);
    $agent = User::factory()->create();
    $client = Client::factory()->for(workspaceOf($agent))->create(['lead_id' => Lead::factory()->for(workspaceOf($agent))->create()->id]);
    Policy::factory()->for($client)->create(['payment_frequency' => PaymentFrequency::Annual, 'effective_date' => '2026-03-01', 'renewal_date' => '2026-11-01']);

    $this->artisan('policies:queue-follow-ups')->assertSuccessful();

    changeBroadcast(fn (array $payload, WorkspaceDataChanged $event) => $event->workspaceId === workspaceOf($agent)->id
        && $payload['topic'] === 'follow-ups'
        && $payload['action'] === 'created'
        && $payload['actor'] === null);
});

it('broadcasts on the workspace’s private channel', function () {
    $event = new WorkspaceDataChanged('workspace-7', new WorkspaceChangeData(ChangeTopic::Clients, ChangeAction::Updated, 'client-1', 'Juan Cruz'), ['id' => 'user-1', 'name' => 'Ana']);

    expect($event->broadcastOn()->name)->toBe('private-workspaces.workspace-7')
        ->and($event->broadcastAs())->toBe('data.changed')
        ->and($event->broadcastWith())->toBe([
            'topic' => 'clients',
            'action' => 'updated',
            'record' => ['id' => 'client-1', 'name' => 'Juan Cruz'],
            'count' => 1,
            'actor' => ['id' => 'user-1', 'name' => 'Ana'],
            'message' => null,
        ]);
});

it('lets members listen on their workspace’s channel only', function () {
    config([
        'broadcasting.default' => 'pusher',
        'broadcasting.connections.pusher.key' => 'test-key',
        'broadcasting.connections.pusher.secret' => 'test-secret',
        'broadcasting.connections.pusher.app_id' => '1',
    ]);
    // Channels are registered on the broadcaster in use at boot (null in tests), so register them on Pusher's.
    app(BroadcastManager::class)->forgetDrivers();
    require base_path('routes/channels.php');
    $agent = User::factory()->create();
    $other = User::factory()->create();
    $this->actingAs($agent);

    $this->postJson('/api/broadcasting/auth', ['socket_id' => '1234.5678', 'channel_name' => 'private-workspaces.'.workspaceOf($agent)->id])
        ->assertOk()
        ->assertJsonStructure(['auth']);

    $this->postJson('/api/broadcasting/auth', ['socket_id' => '1234.5678', 'channel_name' => 'private-workspaces.'.workspaceOf($other)->id])
        ->assertForbidden();
});

it('requires a signed-in agent to listen', function () {
    $this->postJson('/api/broadcasting/auth', ['socket_id' => '1234.5678', 'channel_name' => 'private-workspaces.1'])->assertUnauthorized();
});
