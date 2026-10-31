<?php

declare(strict_types=1);

use App\Models\User;
use App\Notifications\FollowUpsDueNotification;
use App\Notifications\MemberJoinedNotification;
use Illuminate\Notifications\Messages\MailMessage;

it('lists every kind, all on until the user turns one off', function () {
    $agent = User::factory()->create();

    $this->actingAs($agent)->getJson('/api/v1/account/notification-preferences')
        ->assertOk()
        ->assertJsonCount(9, 'data')
        ->assertJsonPath('data.*.enabled', array_fill(0, 9, true));
});

it('saves one switch at a time and keeps the others', function () {
    $agent = User::factory()->create();
    $this->actingAs($agent);

    $this->patchJson('/api/v1/account/notification-preferences', ['type' => 'mention', 'enabled' => false])->assertOk();
    $this->patchJson('/api/v1/account/notification-preferences', ['type' => 'member_joined', 'enabled' => false])
        ->assertOk()
        ->assertJsonFragment(['type' => 'mention', 'enabled' => false])
        ->assertJsonFragment(['type' => 'member_joined', 'enabled' => false])
        ->assertJsonFragment(['type' => 'form_lead', 'enabled' => true]);

    $this->patchJson('/api/v1/account/notification-preferences', ['type' => 'gossip', 'enabled' => 'yes'])
        ->assertJsonValidationErrors(['type', 'enabled']);
});

it('sends a kind to the bell and by email together, or not at all once turned off', function () {
    $agent = User::factory()->create();
    $workspace = workspaceOf($agent);

    expect((new MemberJoinedNotification($workspace, $agent))->via($agent))->toContain('mail', 'broadcast');

    $agent->forceFill(['notification_preferences' => ['member_joined' => false]])->save();

    expect((new MemberJoinedNotification($workspace, $agent))->via($agent))->toBe([])
        ->and((new FollowUpsDueNotification($workspace->id, 2, 0))->via($agent))->toContain('mail');
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
