<?php

declare(strict_types=1);

use App\Enums\LeadSource;
use App\Enums\PaymentFrequency;
use App\Models\Client;
use App\Models\Import;
use App\Models\User;
use App\Support\ImportValue;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => Storage::fake(Import::DISK));

function sampleSheet(string $name): UploadedFile
{
    return new UploadedFile(base_path("tests/Fixtures/imports/{$name}"), $name, null, null, true);
}

/** Uploads a sample sheet and checks it with the guessed columns; returns the import's id. */
function checkedImport(User $agent, string $kind, string $file): string
{
    $id = test()->actingAs($agent)
        ->post(ws($agent).'/imports', ['kind' => $kind, 'file' => sampleSheet($file)], ['Accept' => 'application/json'])
        ->assertCreated()
        ->json('data.id');
    $mapping = Import::findOrFail($id)->mapping;

    test()->putJson(ws($agent)."/imports/{$id}", ['mapping' => $mapping])->assertOk();

    return $id;
}

it('reads the header row and guesses which column is which', function () {
    $agent = User::factory()->create();

    $this->actingAs($agent)
        ->post(ws($agent).'/imports', ['kind' => 'clients', 'file' => sampleSheet('clients.csv')], ['Accept' => 'application/json'])
        ->assertCreated()
        ->assertJsonPath('data.status', 'mapping')
        ->assertJsonPath('data.headers.1', 'Mobile No.')
        ->assertJsonPath('data.sample.0.0', 'Maria Santos')
        ->assertJsonPath('data.mapping', [
            'name' => 0, 'email' => 2, 'phone' => 1, 'address' => 5, 'birthdate' => 3, 'civil_status' => 4, 'notes' => 12,
            'product_name' => 6, 'policy_number' => 7, 'policy_status' => 11, 'premium' => 8, 'payment_frequency' => 9, 'effective_date' => 10,
        ]);
});

it('sorts every row into valid, invalid and duplicates, with the problem on each field', function (string $file) {
    $agent = User::factory()->create();
    Client::factory()->for(workspaceOf($agent))->create(['name' => 'Ana Reyes', 'email' => 'ana.reyes@example.com', 'address' => null]);

    $id = checkedImport($agent, 'clients', $file);

    $this->getJson(ws($agent)."/imports/{$id}")
        ->assertJsonPath('data.status', 'ready')
        ->assertJsonPath('data.counts', ['total' => 20, 'valid' => 6, 'invalid' => 12, 'duplicate' => 2, 'imported' => 0, 'fixable' => 5]);

    $rows = collect($this->getJson(ws($agent)."/imports/{$id}/rows")->json('data'))->keyBy('row_number');

    expect($rows[4])->status->toBe('valid')->same_person_as->toBe(2)
        ->and($rows[5])->status->toBe('duplicate')->same_person_as->toBe(2)
        ->and($rows[6]['errors'])->toHaveKey('email')
        ->and($rows[7]['errors'])->toHaveKey('birthdate')
        ->and($rows[8]['errors'])->toBe(['name' => 'A name is needed.'])
        ->and($rows[9]['errors'])->toHaveKey('premium')
        ->and($rows[10]['errors'])->toBe(['product_name' => 'A policy needs a product.'])
        ->and($rows[12])->status->toBe('duplicate')->duplicate->name->toBe('Ana Reyes')
        ->and($rows[13]['errors'])->toHaveKey('payment_frequency')
        ->and($rows[14]['errors'])->toHaveKey('phone')
        ->and($rows[15]['status'])->toBe('valid');
})->with(['clients.csv', 'clients.xlsx']);

it('suggests safe fixes for near misses, and checks every row again once they are applied', function () {
    $agent = User::factory()->create();
    Client::factory()->for(workspaceOf($agent))->create(['email' => 'ana.reyes@example.com']);
    $id = checkedImport($agent, 'clients', 'clients.csv');

    $suggestions = collect($this->getJson(ws($agent)."/imports/{$id}/rows?status=invalid")->json('data'))
        ->filter(fn (array $row) => $row['suggestions'] !== [])
        ->mapWithKeys(fn (array $row) => [$row['row_number'] => $row['suggestions']]);

    expect($suggestions->all())->toBe([
        17 => ['birthdate' => '1988-12-25'],
        18 => ['email' => 'ben.torres@gmail.com'],
        19 => ['payment_frequency' => 'monthly'],
        20 => ['phone' => '0917 765 4321'],
        21 => ['premium' => '1500'],
    ]);

    $this->postJson(ws($agent)."/imports/{$id}/fixes")->assertOk();

    $this->getJson(ws($agent)."/imports/{$id}")
        ->assertJsonPath('data.status', 'ready')
        ->assertJsonPath('data.counts', ['total' => 20, 'valid' => 11, 'invalid' => 7, 'duplicate' => 2, 'imported' => 0, 'fixable' => 0]);

    $this->postJson(ws($agent)."/imports/{$id}/fixes")->assertJsonValidationErrors('import');
});

it('never guesses a date that could be read either way', function () {
    expect(ImportValue::suggestDate('05/06/1990'))->toBeNull()
        ->and(ImportValue::suggestDate('25/12/1988'))->toBe('1988-12-25')
        ->and(ImportValue::closest('every so often', ['monthly', 'annual']))->toBeNull();
});

it('brings in clients with their policies, skipping duplicates', function () {
    $agent = User::factory()->create();
    Client::factory()->for(workspaceOf($agent))->create(['email' => 'ana.reyes@example.com']);
    $id = checkedImport($agent, 'clients', 'clients.csv');

    $this->postJson(ws($agent)."/imports/{$id}/run", ['update_duplicates' => false])->assertOk();

    $this->getJson(ws($agent)."/imports/{$id}")->assertJsonPath('data.status', 'completed')->assertJsonPath('data.counts.imported', 6);

    $maria = Client::where('email', 'maria.santos@example.com')->sole();
    $elena = Client::where('email', 'elena.garcia@example.com')->sole();

    expect(Client::count())->toBe(6)
        ->and($maria->phone)->toBe('0917 123 4567')
        ->and($maria->policies()->pluck('product_name')->sort()->values()->all())->toBe(['Car Protect', 'Life Plus'])
        ->and($maria->policies()->where('product_name', 'Life Plus')->sole()->premium)->toEqual('24000.00')
        ->and($elena->birthdate->toDateString())->toBe('1990-01-01')
        ->and($elena->civil_status->value)->toBe('widowed')
        ->and($elena->policies()->sole()->payment_frequency)->toBe(PaymentFrequency::SemiAnnual)
        ->and(Client::where('name', 'Paolo Ramos')->sole()->address)->toBeNull()
        ->and(Storage::disk(Import::DISK)->allFiles())->toBe([]);
});

it('fills in what an existing client is missing when asked, without overwriting', function () {
    $agent = User::factory()->create();
    $ana = Client::factory()->for(workspaceOf($agent))->create(['name' => 'Ana R.', 'email' => 'ana.reyes@example.com', 'phone' => null, 'address' => null]);
    $id = checkedImport($agent, 'clients', 'clients.csv');

    $this->postJson(ws($agent)."/imports/{$id}/run", ['update_duplicates' => true])->assertOk();

    expect($ana->fresh())->name->toBe('Ana R.')->phone->toBe('0917 555 0000')->address->toBe('Pasig')
        ->and(collect($this->getJson(ws($agent)."/imports/{$id}/rows?status=updated")->json('data'))->pluck('row_number')->all())->toBe([12]);
});

it('brings in leads as prospects from an import, leaving people who are already clients alone', function () {
    $agent = User::factory()->create();
    $ana = Client::factory()->for(workspaceOf($agent))->create(['email' => 'ana.reyes@example.com']);
    $id = checkedImport($agent, 'leads', 'leads.csv');

    $this->getJson(ws($agent)."/imports/{$id}")->assertJsonPath('data.counts', ['total' => 8, 'valid' => 3, 'invalid' => 3, 'duplicate' => 2, 'imported' => 0, 'fixable' => 0]);

    $this->postJson(ws($agent)."/imports/{$id}/run", ['update_duplicates' => true])->assertOk();

    $leads = workspaceOf($agent)->leads()->orderBy('name')->get();
    expect($leads->pluck('name')->all())->toBe(['Carlo Diaz', 'Joy Castillo', 'Mark Villanueva'])
        ->and($leads->every(fn ($lead) => $lead->source === LeadSource::Import))->toBeTrue()
        ->and($leads[1]->interest->value)->toBe('auto')
        ->and($ana->fresh()->lead_id)->toBeNull();
});

it('needs a name column and each column once', function () {
    $agent = User::factory()->create();
    $id = $this->actingAs($agent)
        ->post(ws($agent).'/imports', ['kind' => 'clients', 'file' => sampleSheet('clients.csv')], ['Accept' => 'application/json'])
        ->json('data.id');

    $this->putJson(ws($agent)."/imports/{$id}", ['mapping' => ['email' => 2, 'phone' => 2]])
        ->assertJsonValidationErrors(['mapping.name', 'mapping.email', 'mapping.phone']);
    $this->putJson(ws($agent)."/imports/{$id}", ['mapping' => ['name' => 0, 'interest' => 1]])
        ->assertJsonValidationErrors('mapping');
});

it('keeps imports to their workspace', function () {
    $agent = User::factory()->create();
    $other = User::factory()->create();
    $id = checkedImport($other, 'clients', 'clients.csv');

    $this->actingAs($agent)->getJson(ws($agent)."/imports/{$id}")->assertNotFound();
    $this->postJson(ws($agent)."/imports/{$id}/run", ['update_duplicates' => false])->assertNotFound();
});
