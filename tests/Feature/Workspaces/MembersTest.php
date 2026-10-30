<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Notifications\WorkspaceInvitationNotification;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;

function inviteTo(User $owner, string $email): string
{
    $url = test()->actingAs($owner)->postJson(ws($owner).'/invitations', ['email' => $email])->assertCreated()->json('accept_url');

    return str($url)->afterLast('/')->toString();
}

it('lists the workspace’s members', function () {
    $owner = User::factory()->create(['name' => 'Ana']);
    $member = User::factory()->create(['name' => 'Ben']);
    workspaceOf($owner)->users()->attach($member, ['role' => 'member']);

    $this->actingAs($member)->getJson(ws($owner).'/members')
        ->assertOk()
        ->assertJsonPath('data.0.user.name', 'Ana')
        ->assertJsonPath('data.0.role.value', 'owner')
        ->assertJsonPath('data.1.user.name', 'Ben')
        ->assertJsonPath('data.1.role.value', 'member');
});

it('invites someone by email and sends them the link', function () {
    Notification::fake();
    $owner = User::factory()->create();

    $this->actingAs($owner)->postJson(ws($owner).'/invitations', ['email' => 'Ben@Example.com'])
        ->assertCreated()
        ->assertJsonPath('data.email', 'ben@example.com')
        ->assertJsonPath('data.role.value', 'member')
        ->assertJsonMissingPath('data.token');

    Notification::assertSentOnDemand(
        WorkspaceInvitationNotification::class,
        fn ($notification, $channels, AnonymousNotifiable $notifiable) => $notifiable->routes['mail'] === 'ben@example.com',
    );
    $this->getJson(ws($owner).'/invitations')->assertJsonCount(1, 'data');
});

it('replaces a pending invite when the same email is invited again', function () {
    Notification::fake();
    $owner = User::factory()->create();

    $firstToken = inviteTo($owner, 'ben@example.com');
    inviteTo($owner, 'ben@example.com');

    expect(WorkspaceInvitation::count())->toBe(1);
    $this->getJson("/api/v1/invitations/{$firstToken}")->assertNotFound();
});

it('does not invite people already in the workspace, or as owner', function () {
    $owner = User::factory()->create(['email' => 'ana@example.com']);

    $this->actingAs($owner)->postJson(ws($owner).'/invitations', ['email' => 'ana@example.com'])->assertJsonValidationErrors('email');
    $this->postJson(ws($owner).'/invitations', ['email' => 'ben@example.com', 'role' => 'owner'])->assertJsonValidationErrors('role');
});

it('only lets the owner manage invites', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    workspaceOf($owner)->users()->attach($member, ['role' => 'member']);

    $this->actingAs($member);
    $this->getJson(ws($owner).'/invitations')->assertForbidden();
    $this->postJson(ws($owner).'/invitations', ['email' => 'ben@example.com'])->assertForbidden();
});

it('shows an invite only to the person it was sent to', function () {
    Notification::fake();
    $owner = User::factory()->create(['name' => 'Ana']);
    workspaceOf($owner)->update(['name' => 'Reyes Insurance']);
    $token = inviteTo($owner, 'ben@example.com');

    $this->actingAs(User::factory()->create())->getJson("/api/v1/invitations/{$token}")->assertForbidden();

    $this->actingAs(User::factory()->create(['email' => 'ben@example.com']))->getJson("/api/v1/invitations/{$token}")
        ->assertOk()
        ->assertJsonPath('data.workspace.name', 'Reyes Insurance')
        ->assertJsonPath('data.invited_by', 'Ana');
});

it('adds the invited user as a member when they accept', function () {
    Notification::fake();
    $owner = User::factory()->create();
    $token = inviteTo($owner, 'ben@example.com');
    $ben = User::factory()->create(['email' => 'ben@example.com']);

    $this->actingAs($ben)->postJson("/api/v1/invitations/{$token}/accept")
        ->assertOk()
        ->assertJsonPath('data.id', workspaceOf($owner)->id)
        ->assertJsonPath('data.role.value', 'member');

    $this->getJson(ws($owner).'/leads')->assertOk();
    $this->postJson("/api/v1/invitations/{$token}/accept")->assertJsonValidationErrors('invitation');
});

it('only accepts an invite for the email it was sent to', function () {
    Notification::fake();
    $owner = User::factory()->create();
    $token = inviteTo($owner, 'ben@example.com');

    $this->actingAs(User::factory()->create(['email' => 'someone@example.com']))
        ->postJson("/api/v1/invitations/{$token}/accept")
        ->assertForbidden();

    expect(workspaceOf($owner)->users()->count())->toBe(1);
});

it('turns away expired invites', function () {
    $invitation = WorkspaceInvitation::factory()->expired()->create(['email' => 'ben@example.com', 'token' => 'old-link']);

    $this->actingAs(User::factory()->create(['email' => 'ben@example.com']))
        ->postJson('/api/v1/invitations/old-link/accept')
        ->assertJsonValidationErrors(['invitation' => 'expired']);

    expect($invitation->workspace->users()->count())->toBe(0);
});

it('lets the owner revoke an invite', function () {
    Notification::fake();
    $owner = User::factory()->create();
    $token = inviteTo($owner, 'ben@example.com');
    $invitation = WorkspaceInvitation::sole();

    $this->deleteJson(ws($owner)."/invitations/{$invitation->id}")->assertNoContent();

    $this->getJson("/api/v1/invitations/{$token}")->assertNotFound();
});

it('lets the owner remove a member, but never the owner', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $workspace = workspaceOf($owner);
    $workspace->users()->attach($member, ['role' => 'member']);
    [$ownerMembership, $memberMembership] = [$workspace->members()->where('user_id', $owner->id)->sole(), $workspace->members()->where('user_id', $member->id)->sole()];

    $this->actingAs($member)->deleteJson(ws($owner)."/members/{$ownerMembership->id}")->assertForbidden();

    $this->actingAs($owner)->deleteJson(ws($owner)."/members/{$ownerMembership->id}")->assertJsonValidationErrors('member');
    $this->deleteJson(ws($owner)."/members/{$memberMembership->id}")->assertNoContent();

    $this->actingAs($member)->getJson(ws($owner))->assertNotFound();
});

it('keeps another workspace’s members and invites out of reach', function () {
    $owner = User::factory()->create();
    $other = Workspace::factory()->ownedBy(User::factory()->create())->create();
    $invitation = WorkspaceInvitation::factory()->for($other)->create();

    $this->actingAs($owner)
        ->deleteJson(ws($owner)."/members/{$other->members()->first()->id}")
        ->assertNotFound();
    $this->deleteJson(ws($owner)."/invitations/{$invitation->id}")->assertNotFound();
});

it('stops inviting once members and pending invites fill the plan’s seats', function () {
    Notification::fake();
    $owner = User::factory()->create();
    inviteTo($owner, 'ben@example.com');

    $this->postJson(ws($owner).'/invitations', ['email' => 'cara@example.com'])
        ->assertJsonValidationErrors(['email' => '2 people']);
    inviteTo($owner, 'ben@example.com');
});

it('gives Premium workspaces more seats', function () {
    Notification::fake();
    $owner = User::factory()->create();
    workspaceOf($owner)->forceFill(['plan' => 'premium', 'plan_ends_at' => now()->addMonth()])->save();
    inviteTo($owner, 'ben@example.com');

    inviteTo($owner, 'cara@example.com');
});

it('turns away an invite once the workspace is full', function () {
    Notification::fake();
    $owner = User::factory()->create();
    $token = inviteTo($owner, 'ben@example.com');
    workspaceOf($owner)->users()->attach(User::factory()->create(), ['role' => 'member']);

    $this->actingAs(User::factory()->create(['email' => 'ben@example.com']))
        ->postJson("/api/v1/invitations/{$token}/accept")
        ->assertJsonValidationErrors(['invitation' => 'full']);
});
