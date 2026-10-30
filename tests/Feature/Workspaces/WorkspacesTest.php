<?php

declare(strict_types=1);

use App\Enums\Plan;
use App\Models\Lead;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('creates a new account without a workspace when onboarding is on', function () {
    config(['insurix.features.workspace-onboarding' => 'free']);

    $this->postJson('/api/v1/auth/register', [
        'name' => 'Ana Reyes',
        'email' => 'ana@example.com',
        'password' => 'correct-horse-battery',
        'password_confirmation' => 'correct-horse-battery',
    ])->assertCreated()
        ->assertJsonPath('data.features', ['workspace-onboarding'])
        ->assertJsonCount(0, 'data.workspaces');

    expect(Workspace::count())->toBe(0);
});

it('lists a Premium feature only for Premium workspaces', function () {
    config(['insurix.features.workspace-switcher' => 'premium']);
    $agent = User::factory()->create();
    $free = Workspace::factory()->withMember($agent)->create();
    $premium = Workspace::factory()->withMember($agent)->create(['plan' => Plan::Premium]);

    $this->actingAs($agent)->getJson('/api/v1/workspaces')
        ->assertOk()
        ->assertJsonFragment(['id' => $free->id, 'plan' => ['value' => 'free', 'label' => 'Free'], 'features' => []])
        ->assertJsonFragment(['id' => $premium->id, 'plan' => ['value' => 'premium', 'label' => 'Premium'], 'features' => ['form-premium-theme', 'workspace-switcher']]);
});

it('lists only the user’s workspaces, with their role in each', function () {
    $agent = User::factory()->create();
    Workspace::factory()->withMember($agent)->create(['name' => 'Helping out']);
    Workspace::factory()->create();

    $this->actingAs($agent)->getJson('/api/v1/workspaces')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment(['name' => 'Helping out', 'role' => ['value' => 'member', 'label' => 'Member']]);
});

it('creates another workspace owned by the user', function () {
    $agent = User::factory()->create();

    $id = $this->actingAs($agent)->postJson('/api/v1/workspaces', ['name' => 'Second desk', 'type' => 'insurance', 'timezone' => 'Asia/Singapore'])
        ->assertCreated()
        ->assertJsonPath('data.role.value', 'owner')
        ->json('data.id');

    $this->getJson("/api/v1/workspaces/{$id}")->assertOk()
        ->assertJsonPath('data.name', 'Second desk')
        ->assertJsonPath('data.timezone', 'Asia/Singapore');
    $this->postJson('/api/v1/workspaces', ['name' => 'Bad', 'type' => 'spaceship', 'timezone' => 'Mars/Olympus'])
        ->assertJsonValidationErrors(['type', 'timezone']);
});

it('lets the owner change the workspace settings, but not a member', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    workspaceOf($owner)->users()->attach($member, ['role' => 'member']);
    $settings = ['name' => 'Reyes Insurance', 'timezone' => 'Asia/Singapore', 'bio' => 'Life and health.'];

    $this->actingAs($member)->putJson(ws($owner), $settings)->assertForbidden();

    $this->actingAs($owner)->putJson(ws($owner), $settings)
        ->assertOk()
        ->assertJsonPath('data.name', 'Reyes Insurance')
        ->assertJsonPath('data.timezone', 'Asia/Singapore')
        ->assertJsonPath('data.bio', 'Life and health.');
    $this->putJson(ws($owner), ['name' => 'X', 'timezone' => 'Mars/Base'])->assertJsonValidationErrors('timezone');
});

it('lets the owner upload and remove the workspace logo', function () {
    Storage::fake(User::UPLOADS_DISK);
    $owner = User::factory()->create();
    $member = User::factory()->create();
    workspaceOf($owner)->users()->attach($member, ['role' => 'member']);
    $logo = fn () => ['image' => UploadedFile::fake()->image('logo.png', 300, 300)];

    $this->actingAs($member)->postJson(ws($owner).'/logo', $logo())->assertForbidden();

    $this->actingAs($owner)->postJson(ws($owner).'/logo', $logo())->assertOk();
    $path = workspaceOf($owner)->logo_path;
    Storage::disk(User::UPLOADS_DISK)->assertExists($path);

    $this->deleteJson(ws($owner).'/logo')->assertOk()->assertJsonPath('data.logo_url', null);
    Storage::disk(User::UPLOADS_DISK)->assertMissing($path);
});

it('hides workspaces the user is not in', function () {
    $outsider = User::factory()->create();
    $agent = User::factory()->create();

    $this->actingAs($outsider);
    $this->getJson(ws($agent))->assertNotFound();
    $this->getJson(ws($agent).'/leads')->assertNotFound();
    $this->getJson(ws($agent).'/members')->assertNotFound();
});

it('keeps each workspace’s records apart for a user in both', function () {
    $agent = User::factory()->create();
    $other = Workspace::factory()->withMember($agent)->create();
    $theirLead = Lead::factory()->for($other)->create();
    Lead::factory()->for(workspaceOf($agent))->create();

    $this->actingAs($agent)->getJson(ws($agent).'/leads')->assertJsonCount(1, 'data');
    $this->getJson("/api/v1/workspaces/{$other->id}/leads")->assertJsonPath('data.0.id', $theirLead->id);
    $this->getJson(ws($agent)."/leads/{$theirLead->id}")->assertNotFound();
});

it('lets members work in the workspace', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    workspaceOf($owner)->users()->attach($member, ['role' => 'member']);

    $this->actingAs($member)
        ->postJson(ws($owner).'/leads', ['name' => 'Ben Cruz', 'phone' => '0917 555 0100'])
        ->assertCreated();

    expect(workspaceOf($owner)->leads()->sole()->activities()->sole()->user_id)->toBe($member->id);
});
