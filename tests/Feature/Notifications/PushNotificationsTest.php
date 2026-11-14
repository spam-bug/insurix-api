<?php

declare(strict_types=1);

use App\Enums\NotificationType;
use App\Models\User;
use App\Notifications\FollowUpsDueNotification;
use NotificationChannels\WebPush\WebPushChannel;

function subscription(): array
{
    return ['endpoint' => 'https://push.example.test/send/abc', 'public_key' => 'public-key', 'auth_token' => 'auth-token', 'content_encoding' => 'aes128gcm'];
}

it('saves a device for push notifications and removes it', function () {
    $agent = User::factory()->create();

    $this->actingAs($agent)->postJson('/api/v1/account/push-subscriptions', subscription())->assertNoContent();
    expect($agent->pushSubscriptions()->pluck('endpoint')->all())->toBe(['https://push.example.test/send/abc']);

    $this->deleteJson('/api/v1/account/push-subscriptions', ['endpoint' => 'https://push.example.test/send/abc'])->assertNoContent();
    expect($agent->pushSubscriptions()->count())->toBe(0);
});

it('moves a device to whoever subscribes on it next', function () {
    [$first, $second] = User::factory()->count(2)->create();

    $this->actingAs($first)->postJson('/api/v1/account/push-subscriptions', subscription())->assertNoContent();
    $this->actingAs($second)->postJson('/api/v1/account/push-subscriptions', subscription())->assertNoContent();

    expect($first->pushSubscriptions()->count())->toBe(0)->and($second->pushSubscriptions()->count())->toBe(1);
});

it('rejects endpoints that are not https', function () {
    $agent = User::factory()->create();

    $this->actingAs($agent)->postJson('/api/v1/account/push-subscriptions', [...subscription(), 'endpoint' => 'http://push.example.test/x'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('endpoint');
});

it('pushes the follow-up reminder only to agents with a device who left it on', function () {
    $agent = User::factory()->create();
    $notification = new FollowUpsDueNotification(workspaceOf($agent)->id, 2, 1);

    expect($notification->via($agent))->not->toContain(WebPushChannel::class);

    $agent->updatePushSubscription('https://push.example.test/send/abc', 'public-key', 'auth-token');
    expect($notification->via($agent->refresh()))->toContain(WebPushChannel::class)
        ->and($notification->toWebPush($agent)->toArray())->toMatchArray(['title' => '3 follow-ups for today', 'data' => ['url' => '/w/'.workspaceOf($agent)->id.'/follow-ups']]);

    $agent->forceFill(['notification_preferences' => [NotificationType::FollowUpsDue->value => false]])->save();
    expect($notification->via($agent->refresh()))->toBe([]);
});
