<?php

declare(strict_types=1);

use App\Enums\PaymentFrequency;
use App\Models\Client;
use App\Models\Document;
use App\Models\FollowUp;
use App\Models\Lead;
use App\Models\Policy;
use App\Models\User;
use App\Models\WorkspaceInvitation;
use App\Notifications\FollowUpsDueNotification;
use App\Notifications\FormLeadNotification;
use App\Notifications\MemberJoinedNotification;
use App\Notifications\RenewalComingUpNotification;
use App\Notifications\StorageAlmostFullNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => Carbon::setTestNow('2026-10-17 02:00:00'));

function teammateOf(User $owner): User
{
    $member = User::factory()->create();
    workspaceOf($owner)->users()->attach($member, ['role' => 'member']);

    return $member;
}

it('tells everyone in the workspace about a new form lead, and stores it for the bell', function () {
    $owner = User::factory()->create();
    $member = teammateOf($owner);
    workspaceOf($owner)->forceFill(['lead_form_token' => 'form-token'])->save();

    $this->postJson('/api/v1/public/lead-forms/form-token', ['name' => 'Gregoria de Jesus', 'email' => 'gregoria@example.com'])->assertCreated();

    foreach ([$owner, $member] as $user) {
        $notification = $user->notifications()->sole();
        expect($notification->type)->toBe('form_lead')
            ->and($notification->workspace_id)->toBe(workspaceOf($owner)->id)
            ->and($notification->data['body'])->toBe('Gregoria de Jesus filled in your form.')
            ->and($notification->data['subject'])->toMatchArray(['kind' => 'lead', 'name' => 'Gregoria de Jesus']);
    }
});

it('broadcasts each notification on its recipient’s private channel', function () {
    $user = User::factory()->create();
    $lead = Lead::factory()->for(workspaceOf($user))->create(['name' => 'Juan Cruz']);
    $notification = new FormLeadNotification($lead);

    expect($notification->via($user))->toContain('broadcast')
        ->and($user->receivesBroadcastNotificationsOn())->toBe("users.{$user->id}")
        ->and($notification->toBroadcast($user)->data)->toMatchArray(['workspace_id' => workspaceOf($user)->id, 'label' => 'Lead', 'title' => 'New lead from your form']);
});

it('tells only the owner when someone accepts an invite', function () {
    Notification::fake();
    $owner = User::factory()->create();
    $member = teammateOf($owner);
    $ben = User::factory()->create(['email' => 'ben@example.com', 'name' => 'Ben']);
    WorkspaceInvitation::factory()->for(workspaceOf($owner))->create(['email' => 'ben@example.com', 'token' => 'join']);

    $this->actingAs($ben)->postJson('/api/v1/invitations/join/accept')->assertOk();

    Notification::assertSentTo($owner, MemberJoinedNotification::class, fn (MemberJoinedNotification $notification) => $notification->member->is($ben));
    Notification::assertNotSentTo([$member, $ben], MemberJoinedNotification::class);
});

it('tells the workspace about a renewal when its reminder is created, once', function () {
    Notification::fake();
    $owner = User::factory()->create();
    $member = teammateOf($owner);
    $client = Client::factory()->for(workspaceOf($owner))->create(['name' => 'Juan Cruz']);
    Policy::factory()->for($client)->create(['product_name' => 'Life Plus', 'payment_frequency' => PaymentFrequency::Annual, 'effective_date' => '2025-11-01', 'renewal_date' => '2026-11-01']);

    $this->artisan('policies:queue-follow-ups')->assertSuccessful();
    $this->artisan('policies:queue-follow-ups')->assertSuccessful();

    Notification::assertSentToTimes($owner, RenewalComingUpNotification::class, 1);
    Notification::assertSentTo($member, RenewalComingUpNotification::class, fn (RenewalComingUpNotification $notification, array $channels, User $notifiable) => $notification->toArray($notifiable)['body'] === 'Juan Cruz’s Life Plus renews Nov 1.');
});

it('sends each member a morning count of follow-ups due today and overdue', function () {
    Notification::fake();
    $owner = User::factory()->create();
    $member = teammateOf($owner);
    $quiet = User::factory()->create();
    $lead = Lead::factory()->for(workspaceOf($owner))->create();
    FollowUp::factory()->for($lead)->create(['due_at' => now()->subDays(2)]);
    FollowUp::factory()->for($lead)->create(['due_at' => now()->addHours(3)]);
    FollowUp::factory()->for($lead)->create(['due_at' => now()->addHour(), 'completed_at' => now()]);
    FollowUp::factory()->for($lead)->create(['due_at' => now()->addDays(3)]);
    FollowUp::factory()->for(Lead::factory()->for(workspaceOf($quiet)))->create(['due_at' => now()->addDays(3)]);

    $this->artisan('follow-ups:notify-due')->assertSuccessful();

    foreach ([$owner, $member] as $user) {
        Notification::assertSentTo($user, FollowUpsDueNotification::class, fn (FollowUpsDueNotification $notification) => $notification->due === 1 && $notification->overdue === 1);
    }
    Notification::assertNotSentTo($quiet, FollowUpsDueNotification::class);
    expect((new FollowUpsDueNotification('workspace', 1, 1))->toArray($owner))->toMatchArray(['title' => '2 follow-ups for today', 'body' => '1 of them overdue. Your Today list has them in order.']);
});

it('tells the owner once when an upload takes storage past 90%', function () {
    Notification::fake();
    Storage::fake(Document::DISK);
    config(['insurix.storage_limit_bytes' => 100 * 1024]);
    $owner = User::factory()->create();
    $member = teammateOf($owner);
    $client = Client::factory()->for(workspaceOf($owner))->create();
    Document::factory()->for($client)->create(['size' => 80 * 1024]);
    $upload = fn (int $kilobytes) => $this->actingAs($member)
        ->postJson(ws($owner).'/documents', ['file' => UploadedFile::fake()->create('scan.pdf', $kilobytes, 'application/pdf'), 'category' => 'other'])
        ->assertCreated();

    $upload(5);
    Notification::assertNothingSent();

    $upload(7);
    $upload(2);

    Notification::assertSentToTimes($owner, StorageAlmostFullNotification::class, 1);
    Notification::assertNotSentTo($member, StorageAlmostFullNotification::class);
});
