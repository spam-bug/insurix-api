<?php

declare(strict_types=1);

use App\Enums\PaymentFrequency;
use App\Events\AgentDataChanged;
use App\Models\Client;
use App\Models\Lead;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;

it('tells the agent when someone fills in their shareable form', function () {
    Event::fake([AgentDataChanged::class]);
    $agent = User::factory()->create(['lead_form_token' => 'form-token']);

    $this->postJson('/api/v1/public/lead-forms/form-token', ['name' => 'Gregoria de Jesus', 'email' => 'gregoria@example.com'])->assertCreated();

    Event::assertDispatched(AgentDataChanged::class, fn (AgentDataChanged $event) => $event->agentId === $agent->id
        && $event->topic === 'leads'
        && $event->message === 'Gregoria de Jesus just filled in your form');
});

it('tells the agent when policy reminders are added to their follow-ups', function () {
    Carbon::setTestNow('2026-10-17 02:00:00');
    Event::fake([AgentDataChanged::class]);
    $agent = User::factory()->create();
    $client = Client::factory()->for($agent)->create(['lead_id' => Lead::factory()->for($agent)->create()->id]);
    Policy::factory()->for($client)->create(['payment_frequency' => PaymentFrequency::Annual, 'effective_date' => '2026-03-01', 'renewal_date' => '2026-11-01']);

    $this->artisan('policies:queue-follow-ups')->assertSuccessful();

    Event::assertDispatched(AgentDataChanged::class, fn (AgentDataChanged $event) => $event->agentId === $agent->id && $event->topic === 'follow-ups');
});

it('broadcasts on the agent’s private channel', function () {
    $event = new AgentDataChanged('agent-7', 'leads', 'Hello');

    expect($event->broadcastOn()->name)->toBe('private-agents.agent-7')
        ->and($event->broadcastAs())->toBe('data.changed')
        ->and($event->broadcastWith())->toBe(['topic' => 'leads', 'message' => 'Hello']);
});

it('lets an agent listen on their own channel only', function () {
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

    $this->postJson('/api/broadcasting/auth', ['socket_id' => '1234.5678', 'channel_name' => "private-agents.{$agent->id}"])
        ->assertOk()
        ->assertJsonStructure(['auth']);

    $this->postJson('/api/broadcasting/auth', ['socket_id' => '1234.5678', 'channel_name' => "private-agents.{$other->id}"])
        ->assertForbidden();
});

it('requires a signed-in agent to listen', function () {
    $this->postJson('/api/broadcasting/auth', ['socket_id' => '1234.5678', 'channel_name' => 'private-agents.1'])->assertUnauthorized();
});
