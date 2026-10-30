<?php

declare(strict_types=1);

use App\Enums\WorkspaceRole;
use App\Models\Lead;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\WorkspaceDeletedNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

it('deletes a workspace once its name is typed, and tells the other members', function () {
    Notification::fake();
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $workspace = workspaceOf($owner);
    $workspace->users()->attach($member, ['role' => WorkspaceRole::Member->value]);
    $url = "/api/v1/workspaces/{$workspace->id}";

    $this->actingAs($member)->deleteJson($url, ['name' => $workspace->name])->assertForbidden();
    $this->actingAs($owner)->deleteJson($url, ['name' => 'Not the name'])->assertUnprocessable()->assertJsonValidationErrors('name');
    $this->deleteJson($url, ['name' => $workspace->name])->assertNoContent();

    expect($workspace->refresh()->trashed())->toBeTrue();
    $this->getJson($url)->assertNotFound();
    $this->actingAs($member)->getJson($url)->assertNotFound();
    Notification::assertSentTo($member, WorkspaceDeletedNotification::class, fn ($notification) => $notification->restorableUntil !== null);
    Notification::assertNotSentTo($owner, WorkspaceDeletedNotification::class);
});

it('lets the owner restore a deleted workspace within the grace period', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $workspace = workspaceOf($owner);
    $workspace->users()->attach($member, ['role' => WorkspaceRole::Member->value]);
    $this->actingAs($owner)->deleteJson("/api/v1/workspaces/{$workspace->id}", ['name' => $workspace->name])->assertNoContent();

    $this->getJson('/api/v1/deleted-workspaces')
        ->assertOk()
        ->assertJsonPath('data.0.id', $workspace->id)
        ->assertJsonPath('data.0.restorable_until', $workspace->refresh()->deleted_at->addDays(30)->toIso8601String());
    $this->actingAs($member)->getJson('/api/v1/deleted-workspaces')->assertJsonCount(0, 'data');
    $this->postJson("/api/v1/deleted-workspaces/{$workspace->id}/restoration")->assertForbidden();

    $this->actingAs($owner)->postJson("/api/v1/deleted-workspaces/{$workspace->id}/restoration")->assertOk()->assertJsonPath('data.id', $workspace->id);
    $this->getJson("/api/v1/workspaces/{$workspace->id}")->assertOk();
});

it('purges workspaces past the grace period, with their data and files', function () {
    Storage::fake('local');
    $owner = User::factory()->create();
    $workspace = workspaceOf($owner);
    Lead::factory()->for($workspace)->create();
    Storage::disk('local')->put("documents/{$workspace->id}/unassigned/file.pdf", 'pdf');
    Storage::disk('local')->put("exports/{$workspace->id}/export.zip", 'zip');
    $workspace->delete();

    $this->travel(29)->days();
    $this->artisan('workspaces:purge')->assertSuccessful();
    expect(Workspace::withTrashed()->find($workspace->id))->not->toBeNull();

    $this->travel(2)->days();
    $this->artisan('workspaces:purge')->assertSuccessful();
    expect(Workspace::withTrashed()->find($workspace->id))->toBeNull()
        ->and(Lead::count())->toBe(0);
    Storage::disk('local')->assertMissing("documents/{$workspace->id}/unassigned/file.pdf");
    Storage::disk('local')->assertMissing("exports/{$workspace->id}/export.zip");
});
