<?php

declare(strict_types=1);

use App\Enums\FormResponseStatus;
use App\Models\Client;
use App\Models\Form;
use App\Models\FormResponse;
use App\Models\Import;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => Storage::fake(Import::DISK));

/** A published quote form that keeps responses without making leads, with one custom question. */
function quoteForm(User $agent): Form
{
    $form = test()->actingAs($agent)->postJson(ws($agent).'/forms', ['name' => 'Quotes', 'template' => 'contact'])->json('data');
    $draft = $form['draft'];
    $draft['settings']['auto_add_leads'] = false;
    $draft['sections'][0]['fields'][] = ['id' => 'budget', 'type' => 'short_text', 'label' => 'Monthly budget', 'help' => null, 'placeholder' => null, 'required' => false, 'options' => [], 'link' => null];
    test()->patchJson(ws($agent)."/forms/{$form['id']}", ['draft' => $draft])->assertOk();
    test()->postJson(ws($agent)."/forms/{$form['id']}/publication")->assertOk();

    return Form::findOrFail($form['id']);
}

function answer(Form $form, array $answers): FormResponse
{
    test()->postJson("/api/v1/public/forms/{$form->token}/responses", ['answers' => $answers])->assertCreated();

    return $form->responses()->latest()->firstOrFail();
}

it('lists a form’s responses, newest first, with who answered and counts per status', function () {
    $agent = User::factory()->create();
    $form = quoteForm($agent);
    answer($form, ['name' => 'Ana Cruz', 'email' => 'ana@example.com']);
    $this->travel(1)->minute();
    answer($form, ['name' => 'Ben Reyes', 'phone' => '0917 555 0100', 'budget' => '₱2,000']);

    $this->actingAs($agent)->getJson(ws($agent)."/form-responses?form_id={$form->id}")
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Ben Reyes')
        ->assertJsonPath('data.0.answers.budget', '₱2,000')
        ->assertJsonPath('data.0.status.value', 'new')
        ->assertJsonPath('data.0.form.name', 'Quotes')
        ->assertJsonPath('data.1.name', 'Ana Cruz')
        ->assertJsonPath('meta.counts', ['new' => 2]);

    $this->getJson(ws($agent)."/form-responses?form_id={$form->id}&status=dismissed")->assertOk()->assertJsonCount(0, 'data');
});

it('dismisses responses and brings them back, but leaves added ones alone', function () {
    $agent = User::factory()->create();
    $form = quoteForm($agent);
    $response = answer($form, ['name' => 'Ana Cruz', 'email' => 'ana@example.com']);

    $this->actingAs($agent)->postJson(ws($agent).'/form-responses/status', ['response_ids' => [$response->id], 'status' => 'dismissed'])
        ->assertOk()
        ->assertJsonPath('data.updated', 1);
    expect($response->refresh()->status)->toBe(FormResponseStatus::Dismissed);

    $this->postJson(ws($agent).'/form-responses/status', ['response_ids' => [$response->id], 'status' => 'new'])->assertOk();
    expect($response->refresh()->status)->toBe(FormResponseStatus::New);

    $response->update(['status' => FormResponseStatus::Added]);
    $this->postJson(ws($agent).'/form-responses/status', ['response_ids' => [$response->id], 'status' => 'dismissed'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('response_ids.0');
});

it('deletes a response but keeps the lead it became', function () {
    $agent = User::factory()->create();
    contactFormOf($agent);
    $form = Form::where('token', 'form-token')->sole();
    answer($form, ['name' => 'Ana Cruz', 'email' => 'ana@example.com']);
    $response = $form->responses()->sole();

    $this->actingAs($agent)->getJson(ws($agent)."/form-responses?lead_id={$response->lead_id}")->assertJsonPath('data.0.id', $response->id);
    $this->deleteJson(ws($agent)."/form-responses/{$response->id}")->assertNoContent();

    expect(FormResponse::count())->toBe(0)->and(Lead::count())->toBe(1);
});

it('starts an import from responses, matching linked questions, and marks them added once saved', function () {
    $agent = User::factory()->create();
    $form = quoteForm($agent);
    $ana = answer($form, ['name' => 'Ana Cruz', 'email' => 'ana@example.com', 'interest' => 'health', 'budget' => '₱2,000']);
    $ben = answer($form, ['name' => 'Ben Reyes', 'phone' => '0917 555 0100']);
    $existing = Client::factory()->for(workspaceOf($agent))->create(['email' => 'carla@example.com']);
    $carla = answer($form, ['name' => 'Carla Diaz', 'email' => 'carla@example.com']);

    $id = $this->actingAs($agent)->postJson(ws($agent)."/forms/{$form->id}/response-imports", ['kind' => 'leads', 'response_ids' => [$ana->id, $ben->id, $carla->id]])
        ->assertCreated()
        ->assertJsonPath('data.kind', 'leads')
        ->assertJsonPath('data.status', 'mapping')
        ->assertJsonPath('data.headers', ['Full name', 'Email', 'Phone', 'What are you interested in?', 'Message', 'Monthly budget'])
        ->assertJsonPath('data.mapping', ['name' => 0, 'email' => 1, 'phone' => 2, 'interest' => 3, 'notes' => 4])
        ->json('data.id');

    $this->putJson(ws($agent)."/imports/{$id}", ['mapping' => Import::findOrFail($id)->mapping])->assertOk();
    $this->postJson(ws($agent)."/imports/{$id}/run", ['update_duplicates' => false])->assertOk();

    $lead = Lead::where('email', 'ana@example.com')->sole();
    expect($lead->interest->value)->toBe('health')
        ->and($ana->refresh())->status->toBe(FormResponseStatus::Added)->lead_id->toBe($lead->id)
        ->and($ben->refresh()->lead_id)->toBe(Lead::where('name', 'Ben Reyes')->sole()->id)
        ->and($carla->refresh()->status)->toBe(FormResponseStatus::New)
        ->and(Lead::where('email', 'carla@example.com')->exists())->toBeFalse()
        ->and($existing->exists)->toBeTrue();
});

it('adds responses to Clients, filling in an existing client when asked', function () {
    $agent = User::factory()->create();
    $form = quoteForm($agent);
    $client = Client::factory()->for(workspaceOf($agent))->create(['email' => 'ana@example.com', 'phone' => null]);
    $ana = answer($form, ['name' => 'Ana Cruz', 'email' => 'ana@example.com', 'phone' => '0917 555 0100']);

    $id = $this->actingAs($agent)->postJson(ws($agent)."/forms/{$form->id}/response-imports", ['kind' => 'clients', 'response_ids' => [$ana->id]])
        ->assertCreated()
        ->assertJsonPath('data.mapping', ['name' => 0, 'email' => 1, 'phone' => 2, 'notes' => 4])
        ->json('data.id');
    $this->putJson(ws($agent)."/imports/{$id}", ['mapping' => Import::findOrFail($id)->mapping])->assertOk();
    $this->postJson(ws($agent)."/imports/{$id}/run", ['update_duplicates' => true])->assertOk();

    expect($client->refresh()->phone)->not->toBeNull()
        ->and($ana->refresh())->status->toBe(FormResponseStatus::Added)->client_id->toBe($client->id);
});

it('only imports new responses of that form', function () {
    $agent = User::factory()->create();
    $form = quoteForm($agent);
    $response = answer($form, ['name' => 'Ana Cruz', 'email' => 'ana@example.com']);
    $response->update(['status' => FormResponseStatus::Added]);
    $other = answer(contactFormOf(User::factory()->create(), 'other-token'), ['name' => 'Ben', 'email' => 'ben@example.com']);

    $this->actingAs($agent)->postJson(ws($agent)."/forms/{$form->id}/response-imports", ['kind' => 'leads', 'response_ids' => [$response->id, $other->id]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['response_ids.0', 'response_ids.1']);
});

it('keeps responses to other workspaces out of reach', function () {
    $agent = User::factory()->create();
    $other = User::factory()->create();
    $response = answer(quoteForm($other), ['name' => 'Ana Cruz', 'email' => 'ana@example.com']);

    $this->actingAs($agent)->deleteJson(ws($agent)."/form-responses/{$response->id}")->assertNotFound();
    $this->postJson(ws($agent).'/form-responses/status', ['response_ids' => [$response->id], 'status' => 'dismissed'])->assertUnprocessable();
});
