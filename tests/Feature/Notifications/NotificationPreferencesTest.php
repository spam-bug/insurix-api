<?php

declare(strict_types=1);

use App\Models\User;
use App\Notifications\FollowUpsDueNotification;
use App\Notifications\MemberJoinedNotification;
use Illuminate\Notifications\Messages\MailMessage;

it('lists every kind with in app on and email on only for the personal or urgent ones', function () {
    $agent = User::factory()->create();

    $preferences = collect($this->actingAs($agent)->getJson('/api/v1/account/notification-preferences')->assertOk()->json('data'))->keyBy('type');

    expect($preferences)->toHaveCount(8)
        ->and($preferences->every(fn (array $preference) => $preference['in_app']))->toBeTrue()
        ->and($preferences->where('email', true)->keys()->sort()->values()->all())
        ->toBe(['follow_ups_due', 'form_lead', 'mention', 'renewal_coming_up']);
});

it('saves one switch at a time and keeps the others', function () {
    $agent = User::factory()->create();
    $this->actingAs($agent);

    $this->patchJson('/api/v1/account/notification-preferences', ['type' => 'mention', 'channel' => 'email', 'enabled' => false])->assertOk();
    $this->patchJson('/api/v1/account/notification-preferences', ['type' => 'member_joined', 'channel' => 'email', 'enabled' => true])
        ->assertOk()
        ->assertJsonFragment(['type' => 'mention', 'in_app' => true, 'email' => false])
        ->assertJsonFragment(['type' => 'member_joined', 'in_app' => true, 'email' => true]);

    $this->patchJson('/api/v1/account/notification-preferences', ['type' => 'gossip', 'channel' => 'sms', 'enabled' => 'yes'])
        ->assertJsonValidationErrors(['type', 'channel', 'enabled']);
});

it('sends each notification only through the channels the user left on', function () {
    $agent = User::factory()->create();
    $workspace = workspaceOf($agent);
    $workspaceId = $workspace->id;

    expect((new FollowUpsDueNotification($workspaceId, 2, 0))->via($agent))->toContain('mail', 'broadcast')
        ->and((new MemberJoinedNotification($workspace, $agent))->via($agent))->not->toContain('mail');

    $agent->forceFill(['notification_preferences' => ['follow_ups_due' => ['in_app' => false, 'email' => true]]])->save();

    expect((new FollowUpsDueNotification($workspaceId, 2, 0))->via($agent))->toBe(['mail']);
});

it('emails the notification with a link that opens it in the app', function () {
    $agent = User::factory()->create();
    $workspace = workspaceOf($agent);

    $mail = (new FollowUpsDueNotification($workspace->id, 2, 1))->toMail($agent);

    expect($mail)->toBeInstanceOf(MailMessage::class)
        ->and($mail->subject)->toBe("3 follow-ups for today · {$workspace->name}")
        ->and($mail->introLines)->toBe(['1 of them overdue. Your Today list has them in order.'])
        ->and($mail->actionUrl)->toBe(config('app.frontend_url')."/w/{$workspace->id}/open?type=follow_ups_due");
});
