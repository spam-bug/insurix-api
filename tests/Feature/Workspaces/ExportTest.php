<?php

declare(strict_types=1);

use App\Enums\WorkspaceRole;
use App\Models\Lead;
use App\Models\User;
use App\Models\WorkspaceExport;
use App\Notifications\WorkspaceExportReadyNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

it('builds a zip of CSVs for the owner and tells them when it’s ready', function () {
    Storage::fake('local');
    Notification::fake();
    $owner = User::factory()->create();
    Lead::factory()->for(workspaceOf($owner))->create(['name' => 'Maisie Flynn']);

    $id = $this->actingAs($owner)->postJson(ws($owner).'/exports')->assertAccepted()->json('data.id');

    $this->getJson(ws($owner).'/exports')->assertOk()->assertJsonPath('data.0.status.value', 'ready');
    Notification::assertSentTo($owner, WorkspaceExportReadyNotification::class);

    $export = WorkspaceExport::findOrFail($id);
    $zip = new ZipArchive;
    $zip->open(Storage::disk('local')->path($export->path));
    expect($zip->getFromName('leads.csv'))->toContain('Maisie Flynn')->not->toContain('workspace_id')
        ->and($zip->getFromName('clients.csv'))->toStartWith('id,');

    $this->get(ws($owner)."/exports/{$id}/download")->assertOk()->assertDownload();
});

it('keeps exports to the owner, and prunes them after a week', function () {
    Storage::fake('local');
    $owner = User::factory()->create();
    $member = User::factory()->create();
    workspaceOf($owner)->users()->attach($member, ['role' => WorkspaceRole::Member->value]);

    $this->actingAs($member)->postJson(ws($owner).'/exports')->assertForbidden();
    $this->getJson(ws($owner).'/exports')->assertForbidden();

    $id = $this->actingAs($owner)->postJson(ws($owner).'/exports')->json('data.id');
    $path = WorkspaceExport::findOrFail($id)->path;

    $this->travel(8)->days();
    $this->artisan('exports:prune')->assertSuccessful();

    expect(WorkspaceExport::find($id))->toBeNull();
    Storage::disk('local')->assertMissing($path);
});
