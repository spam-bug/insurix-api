<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Document;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => Storage::fake(Document::DISK));

function unassignedDocument(User $agent, array $attributes = []): Document
{
    return Document::factory()->create(['client_id' => null, 'workspace_id' => workspaceOf($agent)->id, ...$attributes]);
}

it('uploads a file to the inbox without a client, and refuses a policy without one', function () {
    $agent = User::factory()->create();

    $this->actingAs($agent)
        ->postJson(ws($agent).'/documents', ['file' => UploadedFile::fake()->image('id-front.jpg'), 'category' => 'valid_id'])
        ->assertCreated()
        ->assertJsonPath('data.client', null)
        ->assertJsonPath('data.previewable', true);

    expect(Document::sole()->path)->toStartWith('documents/'.workspaceOf($agent)->id.'/unassigned/');

    $policy = Policy::factory()->for(Client::factory()->for(workspaceOf($agent)))->create();
    $this->postJson(ws($agent).'/documents', ['file' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'), 'category' => 'other', 'policy_id' => $policy->id])
        ->assertJsonValidationErrors('policy_id');
});

it('uploads from the Documents page straight to a client of the workspace only', function () {
    $agent = User::factory()->create();
    $client = Client::factory()->for(workspaceOf($agent))->create();
    $stranger = Client::factory()->create();

    $this->actingAs($agent)
        ->postJson(ws($agent).'/documents', ['file' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'), 'category' => 'other', 'client_id' => $client->id])
        ->assertCreated()
        ->assertJsonPath('data.client.id', $client->id);

    $this->postJson(ws($agent).'/documents', ['file' => UploadedFile::fake()->create('b.pdf', 10, 'application/pdf'), 'category' => 'other', 'client_id' => $stranger->id])
        ->assertJsonValidationErrors('client_id');
});

it('refuses uploads over the workspace’s storage limit', function () {
    config(['insurix.plans.free.storage_bytes' => 50 * 1024]);
    $agent = User::factory()->create();
    unassignedDocument($agent, ['size' => 40 * 1024]);

    $this->actingAs($agent)
        ->postJson(ws($agent).'/documents', ['file' => UploadedFile::fake()->create('big.pdf', 20, 'application/pdf'), 'category' => 'other'])
        ->assertJsonValidationErrors(['file' => 'storage is full']);
});

it('assigns an inbox file to a client, and keeps the client when an edit leaves it out', function () {
    $agent = User::factory()->create();
    $client = Client::factory()->for(workspaceOf($agent))->create();
    $policy = Policy::factory()->for($client)->create();
    $document = unassignedDocument($agent);

    $this->actingAs($agent)
        ->putJson(ws($agent)."/documents/{$document->id}", ['name' => 'ID', 'category' => 'valid_id', 'client_id' => $client->id, 'policy_id' => $policy->id])
        ->assertOk()
        ->assertJsonPath('data.client.id', $client->id)
        ->assertJsonPath('data.policy.id', $policy->id);

    $this->putJson(ws($agent)."/documents/{$document->id}", ['name' => 'Renamed', 'category' => 'valid_id', 'policy_id' => $policy->id])
        ->assertOk()
        ->assertJsonPath('data.client.id', $client->id);
});

it('assigns several files at once, dropping policies of the client they leave', function () {
    $agent = User::factory()->create();
    $from = Client::factory()->for(workspaceOf($agent))->create();
    $to = Client::factory()->for(workspaceOf($agent))->create();
    $inbox = unassignedDocument($agent);
    $moved = Document::factory()->for($from)->create(['policy_id' => Policy::factory()->for($from)->create()->id]);

    $this->actingAs($agent)
        ->postJson(ws($agent).'/documents/bulk/assign', ['ids' => [$inbox->id, $moved->id], 'client_id' => $to->id])
        ->assertOk()
        ->assertJson(['assigned' => 2]);

    expect($inbox->fresh()->client_id)->toBe($to->id)
        ->and($moved->fresh())->client_id->toBe($to->id)->policy_id->toBeNull();

    $this->postJson(ws($agent).'/documents/bulk/assign', ['ids' => [Document::factory()->create()->id], 'client_id' => $to->id])
        ->assertJsonValidationErrors('ids.0');
});

it('lists inbox files and summarizes types, inbox and storage', function () {
    $agent = User::factory()->create();
    $inbox = unassignedDocument($agent, ['category' => 'valid_id', 'size' => 1000]);
    Document::factory()->for(Client::factory()->for(workspaceOf($agent)))->create(['category' => 'policy_contract', 'size' => 500]);

    $this->actingAs($agent)->getJson(ws($agent).'/documents?unassigned=1')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $inbox->id);

    $this->getJson(ws($agent).'/documents/summary')
        ->assertJsonPath('data.total', 2)
        ->assertJsonPath('data.unassigned', 1)
        ->assertJsonPath('data.by_category.valid_id', 1)
        ->assertJsonPath('data.by_category.medical_record', 0)
        ->assertJsonPath('data.storage', ['used' => 1500, 'limit' => config('insurix.plans.free.storage_bytes')]);
});

it('serves a file inline for the preview', function () {
    $agent = User::factory()->create();
    Storage::disk(Document::DISK)->put('documents/x/photo.jpg', 'image');
    $document = unassignedDocument($agent, ['path' => 'documents/x/photo.jpg', 'mime_type' => 'image/jpeg', 'original_name' => 'photo.jpg', 'name' => 'Photo']);

    expect($this->actingAs($agent)->get(ws($agent)."/documents/{$document->id}/download?inline=1")->headers->get('content-disposition'))->toStartWith('inline')
        ->and($this->get(ws($agent)."/documents/{$document->id}/download")->headers->get('content-disposition'))->toStartWith('attachment');
});
