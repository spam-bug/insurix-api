<?php

use App\Enums\DocumentCategory;
use App\Models\Client;
use App\Models\Document;
use App\Models\Lead;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Carbon::setTestNow('2026-10-17 02:00:00');
    Storage::fake(Document::DISK);
});

it('uploads a document for a client, linked to one of their policies', function () {
    $agent = User::factory()->create();
    $client = Client::factory()->for($agent)->create(['name' => 'Ana Reyes']);
    $policy = Policy::factory()->for($client)->create(['product_name' => 'Life Plus']);

    $response = $this->actingAs($agent)
        ->postJson(route('api.v1.clients.documents.store', $client), [
            'file' => UploadedFile::fake()->create('Life Plus contract.pdf', 300, 'application/pdf'),
            'category' => 'policy_contract',
            'policy_id' => $policy->id,
            'notes' => 'Signed copy',
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Life Plus contract')
        ->assertJsonPath('data.category', ['value' => 'policy_contract', 'label' => 'Policy contract'])
        ->assertJsonPath('data.client', ['id' => $client->id, 'name' => 'Ana Reyes'])
        ->assertJsonPath('data.policy.product_name', 'Life Plus')
        ->assertJsonPath('data.original_name', 'Life Plus contract.pdf')
        ->assertJsonPath('data.notes', 'Signed copy');

    $document = Document::sole();
    expect($document->user_id)->toBe($agent->id)
        ->and($document->path)->toStartWith("documents/{$agent->id}/{$client->id}/")
        ->and($response->json('data.download_path'))->toBe("/documents/{$document->id}/download");
    Storage::disk(Document::DISK)->assertExists($document->path);
});

it('keeps documents off the public disk', function () {
    expect(Document::DISK)->not->toBe(User::UPLOADS_DISK)
        ->and(config('filesystems.disks.'.Document::DISK.'.visibility'))->not->toBe('public');
});

it('uses the given name over the file name', function () {
    $client = Client::factory()->create();

    $this->actingAs($client->user)
        ->postJson(route('api.v1.clients.documents.store', $client), [
            'file' => UploadedFile::fake()->image('IMG_2041.jpg'),
            'name' => 'Driver’s license',
            'category' => 'valid_id',
            'expires_on' => '2027-02-01',
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Driver’s license')
        ->assertJsonPath('data.expires_on', '2027-02-01')
        ->assertJsonPath('data.expiry_status', null);
});

it('rejects files that are too big or of the wrong type, and policies of other clients', function () {
    $client = Client::factory()->create();
    $otherPolicy = Policy::factory()->for(Client::factory()->for($client->user))->create();
    $this->actingAs($client->user);

    $this->postJson(route('api.v1.clients.documents.store', $client), [
        'file' => UploadedFile::fake()->create('big.pdf', 11000, 'application/pdf'),
        'category' => 'other',
    ])->assertJsonValidationErrors(['file' => 'under 10 MB']);

    $this->postJson(route('api.v1.clients.documents.store', $client), [
        'file' => UploadedFile::fake()->create('run.exe', 10, 'application/x-msdownload'),
        'category' => 'other',
    ])->assertJsonValidationErrors('file');

    $this->postJson(route('api.v1.clients.documents.store', $client), [
        'file' => UploadedFile::fake()->create('ok.pdf', 10, 'application/pdf'),
        'category' => 'other',
        'policy_id' => $otherPolicy->id,
    ])->assertJsonValidationErrors(['policy_id' => 'this client’s policies']);

    expect(Document::count())->toBe(0);
});

it('keeps agents out of each other’s clients and documents', function () {
    $agent = User::factory()->create();
    $theirs = Document::factory()->create();
    $this->actingAs($agent);

    $this->postJson(route('api.v1.clients.documents.store', $theirs->client), [
        'file' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'),
        'category' => 'other',
    ])->assertForbidden();
    $this->getJson(route('api.v1.documents.show', $theirs))->assertForbidden();
    $this->getJson(route('api.v1.documents.download', $theirs))->assertForbidden();
    $this->putJson(route('api.v1.documents.update', $theirs), ['name' => 'Mine', 'category' => 'other'])->assertForbidden();
    $this->deleteJson(route('api.v1.documents.destroy', $theirs))->assertForbidden();
    $this->getJson(route('api.v1.documents.index'))->assertJsonCount(0, 'data');
});

it('downloads the file under the document’s name', function () {
    $client = Client::factory()->create();
    $this->actingAs($client->user);
    $this->postJson(route('api.v1.clients.documents.store', $client), [
        'file' => UploadedFile::fake()->create('scan-001.pdf', 50, 'application/pdf'),
        'name' => 'Application form',
        'category' => 'application_form',
    ])->assertCreated();

    $this->get(route('api.v1.documents.download', Document::sole()))
        ->assertOk()
        ->assertDownload('Application form.pdf');
});

it('edits the details and deletes the document with its file', function () {
    $client = Client::factory()->create();
    $policy = Policy::factory()->for($client)->create();
    $this->actingAs($client->user);
    $this->postJson(route('api.v1.clients.documents.store', $client), [
        'file' => UploadedFile::fake()->create('scan.pdf', 50, 'application/pdf'),
        'category' => 'other',
    ])->assertCreated();
    $document = Document::sole();

    $this->putJson(route('api.v1.documents.update', $document), ['name' => '', 'category' => 'receipt'])->assertJsonValidationErrors('name');
    $this->putJson(route('api.v1.documents.update', $document), [
        'name' => 'October receipt',
        'category' => 'receipt',
        'policy_id' => $policy->id,
        'expires_on' => null,
    ])
        ->assertOk()
        ->assertJsonPath('data.name', 'October receipt')
        ->assertJsonPath('data.category.value', 'receipt')
        ->assertJsonPath('data.policy.id', $policy->id);

    $this->deleteJson(route('api.v1.documents.destroy', $document))->assertNoContent();
    expect(Document::count())->toBe(0);
    Storage::disk(Document::DISK)->assertMissing($document->path);
});

it('lists documents by client, category and search, newest first', function () {
    $agent = User::factory()->create();
    $ana = Client::factory()->for($agent)->create(['name' => 'Ana Reyes']);
    $ben = Client::factory()->for($agent)->create(['name' => 'Ben Cruz']);
    $older = Document::factory()->for($ana)->create(['name' => 'Passport', 'category' => DocumentCategory::ValidId, 'created_at' => now()->subDay()]);
    $newer = Document::factory()->for($ana)->create(['name' => 'Contract', 'category' => DocumentCategory::PolicyContract]);
    $bens = Document::factory()->for($ben)->create(['name' => 'Receipt']);
    $this->actingAs($agent);

    expect($this->getJson(route('api.v1.documents.index'))->json('data.*.id'))->toBe([$bens->id, $newer->id, $older->id])
        ->and($this->getJson(route('api.v1.documents.index', ['client_id' => $ana->id]))->json('data.*.id'))->toBe([$newer->id, $older->id])
        ->and($this->getJson(route('api.v1.documents.index', ['category' => 'valid_id']))->json('data.*.id'))->toBe([$older->id])
        ->and($this->getJson(route('api.v1.documents.index', ['search' => 'ben']))->json('data.*.id'))->toBe([$bens->id]);
});

it('lists expired and expiring documents, soonest first', function () {
    $client = Client::factory()->create();
    $expired = Document::factory()->for($client)->expiresOn('2026-10-01')->create();
    $soon = Document::factory()->for($client)->expiresOn('2026-11-10')->create();
    Document::factory()->for($client)->expiresOn('2027-06-01')->create();
    Document::factory()->for($client)->create();

    $response = $this->actingAs($client->user)->getJson(route('api.v1.documents.index', ['expiring' => 1]))->assertOk();

    expect($response->json('data.*.id'))->toBe([$expired->id, $soon->id])
        ->and($response->json('data.*.expiry_status'))->toBe(['expired', 'expiring']);
});

it('suggests asking for new copies of documents that expire', function () {
    $client = Client::factory()->create(['name' => 'Ana Reyes']);
    Lead::factory()->for($client->user)->create();
    Document::factory()->for($client)->expiresOn('2026-11-02')->create(['name' => 'Valid ID']);

    $suggestion = collect($this->actingAs($client->user)->getJson(route('api.v1.insights.next-actions'))->json('data'))->firstWhere('code', 'documents_expiring');

    expect($suggestion)->toMatchArray([
        'title' => '1 client document expires in the next 30 days',
        'detail' => "Ana Reyes's Valid ID (Nov 2). Ask for new copies.",
        'count' => 1,
    ]);
});
