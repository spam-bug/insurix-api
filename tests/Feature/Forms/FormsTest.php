<?php

declare(strict_types=1);

use App\Enums\FormResponseStatus;
use App\Enums\ImportField;
use App\Models\Client;
use App\Models\Form;
use App\Models\Lead;
use App\Models\User;
use App\Models\Workspace;

/** A published form with one section of the given fields, not adding responses to Leads unless asked. */
function publishedForm(User $user, array $fields, bool $addToLeads = false): Form
{
    $form = test()->actingAs($user)->postJson(ws($user).'/forms', ['name' => 'Survey'])->assertCreated()->json('data');
    $draft = $form['draft'];
    $draft['sections'][0]['fields'] = $fields;
    $draft['settings']['auto_add_leads'] = $addToLeads;

    test()->patchJson(ws($user)."/forms/{$form['id']}", ['draft' => $draft])->assertOk();
    test()->postJson(ws($user)."/forms/{$form['id']}/publication")->assertOk();

    return Form::findOrFail($form['id']);
}

function question(string $id, string $type, array $extra = []): array
{
    return ['id' => $id, 'type' => $type, 'label' => ucfirst($id), 'help' => null, 'placeholder' => null, 'required' => false, 'options' => [], 'link' => null, ...$extra];
}

it('starts a new workspace with a published default contact form', function () {
    $agent = User::factory()->create();

    $this->actingAs($agent)->postJson('/api/v1/workspaces', ['name' => 'Second desk', 'type' => 'insurance', 'timezone' => 'Asia/Manila'])->assertCreated();

    $form = Workspace::where('name', 'Second desk')->sole()->forms()->sole();
    expect($form->is_default)->toBeTrue()
        ->and($form->status->value)->toBe('published')
        ->and($form->template->value)->toBe('contact');
});

it('creates forms from a template or blank, the first one as default', function () {
    $agent = User::factory()->create();

    $this->actingAs($agent)->postJson(ws($agent).'/forms', ['name' => 'Quotes', 'template' => 'quote_request'])
        ->assertCreated()
        ->assertJsonPath('data.is_default', true)
        ->assertJsonPath('data.status.value', 'draft')
        ->assertJsonPath('data.draft.sections.1.title', 'What you need')
        ->assertJsonPath('data.published', null);

    $this->postJson(ws($agent).'/forms', ['name' => 'Event sign-up'])
        ->assertCreated()
        ->assertJsonPath('data.is_default', false)
        ->assertJsonPath('data.draft.settings.title', 'Event sign-up')
        ->assertJsonPath('data.draft.sections.0.fields', [])
        ->assertJsonPath('data.draft.settings.auto_add_leads', false);

    $this->getJson(ws($agent).'/forms')->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('meta.limit', 3);
});

it('limits Free workspaces to three forms, but not Premium', function () {
    $agent = User::factory()->create();
    $this->actingAs($agent);
    foreach (range(1, 3) as $number) {
        $this->postJson(ws($agent).'/forms', ['name' => "Form {$number}"])->assertCreated();
    }

    $this->postJson(ws($agent).'/forms', ['name' => 'One more'])->assertUnprocessable()->assertJsonPath('errors.name.0', 'Free workspaces can have up to 3 forms.');

    workspaceOf($agent)->forceFill(['plan' => 'premium'])->save();
    $this->postJson(ws($agent).'/forms', ['name' => 'One more'])->assertCreated();
});

it('keeps a linked field’s type and choices, whatever the builder sends', function () {
    $agent = User::factory()->create();
    $form = $this->actingAs($agent)->postJson(ws($agent).'/forms', ['name' => 'Quick'])->json('data');
    $draft = $form['draft'];
    $draft['sections'][0]['fields'] = [question('interest', 'short_text', ['label' => 'What do you need?', 'link' => 'interest'])];

    $this->patchJson(ws($agent)."/forms/{$form['id']}", ['draft' => $draft])
        ->assertOk()
        ->assertJsonPath('data.draft.sections.0.fields.0.type', 'dropdown')
        ->assertJsonPath('data.draft.sections.0.fields.0.label', 'What do you need?')
        ->assertJsonPath('data.draft.sections.0.fields.0.options.0', ['id' => 'life', 'label' => 'Life'])
        ->assertJsonPath('data.has_unpublished_changes', true);
});

it('rejects drafts with a bad shape', function (array $fields, string $error) {
    $agent = User::factory()->create();
    $form = $this->actingAs($agent)->postJson(ws($agent).'/forms', ['name' => 'Quick'])->json('data');
    $draft = $form['draft'];
    $draft['sections'][0]['fields'] = $fields;

    $this->patchJson(ws($agent)."/forms/{$form['id']}", ['draft' => $draft])->assertUnprocessable()->assertJsonValidationErrors($error);
})->with([
    'unknown type' => [[question('a', 'signature')], 'draft.sections.0.fields.0.type'],
    'unsafe id' => [[question('a.b', 'short_text')], 'draft.sections.0.fields.0.id'],
    'repeated id' => [[question('a', 'short_text'), question('a', 'email')], 'draft.sections.0.fields.0.id'],
    'detail linked twice' => [[question('a', 'email', ['link' => 'email']), question('b', 'email', ['link' => 'email'])], 'draft.sections.0.fields.0.link'],
]);

it('publishes only complete forms, without needing lead fields', function () {
    $agent = User::factory()->create();
    $form = $this->actingAs($agent)->postJson(ws($agent).'/forms', ['name' => 'Survey'])->json('data');

    $this->postJson(ws($agent)."/forms/{$form['id']}/publication")
        ->assertUnprocessable()
        ->assertJsonPath('errors.draft', ['Add at least one question.']);

    $draft = $form['draft'];
    $draft['sections'][0]['fields'] = [question('colour', 'single_choice', ['label' => 'Favourite colour'])];
    $this->patchJson(ws($agent)."/forms/{$form['id']}", ['draft' => $draft]);
    $this->postJson(ws($agent)."/forms/{$form['id']}/publication")
        ->assertUnprocessable()
        ->assertJsonPath('errors.draft', ['“Favourite colour” needs at least one choice.']);

    $draft['sections'][0]['fields'][0]['options'] = [['id' => 'blue', 'label' => 'Blue']];
    $this->patchJson(ws($agent)."/forms/{$form['id']}", ['draft' => $draft]);
    $this->postJson(ws($agent)."/forms/{$form['id']}/publication")
        ->assertOk()
        ->assertJsonPath('data.status.value', 'published')
        ->assertJsonPath('data.has_unpublished_changes', false);
});

it('shows visitors the published form, never a draft', function () {
    $agent = User::factory()->create();
    $form = $this->actingAs($agent)->postJson(ws($agent).'/forms', ['name' => 'Survey'])->json('data');

    $this->getJson("/api/v1/public/forms/{$form['token']}")->assertNotFound();

    $published = contactFormOf($agent, 'contact-token');
    $this->getJson('/api/v1/public/forms/contact-token')
        ->assertOk()
        ->assertJsonPath('data.form.status', 'published')
        ->assertJsonPath('data.form.document.settings.title', 'Get in touch')
        ->assertJsonPath('data.agent.name', workspaceOf($agent)->name)
        ->assertJsonMissingPath('data.form.document.token');

    $this->postJson(ws($agent)."/forms/{$published->id}/publication", [])->assertOk();
    $this->deleteJson(ws($agent)."/forms/{$published->id}/publication")->assertOk();
    $this->getJson('/api/v1/public/forms/contact-token')->assertOk()->assertJsonPath('data.form.document', null);
    $this->postJson('/api/v1/public/forms/contact-token/responses', ['answers' => ['name' => 'Juan', 'email' => 'juan@example.com']])->assertForbidden();
});

it('turns a contact form response into a prospect', function () {
    $agent = User::factory()->create();
    contactFormOf($agent);

    $this->postJson('/api/v1/public/forms/form-token/responses', ['answers' => [
        'name' => 'Gregoria de Jesus', 'email' => 'gregoria@example.com', 'interest' => 'health', 'notes' => 'Call me after 5',
    ]])->assertCreated();

    $lead = Lead::sole();
    expect($lead->workspace_id)->toBe(workspaceOf($agent)->id)
        ->and($lead->name)->toBe('Gregoria de Jesus')
        ->and($lead->interest->value)->toBe('health')
        ->and($lead->notes)->toBe('Call me after 5')
        ->and($lead->source->value)->toBe('shareable_form');

    $response = workspaceOf($agent)->formResponses()->sole();
    expect($response->status)->toBe(FormResponseStatus::Added)
        ->and($response->lead_id)->toBe($lead->id)
        ->and(array_column($response->fields, 'id'))->toBe(['name', 'email', 'phone', 'interest', 'notes']);
});

it('keeps responses of forms that don’t add leads, without making one', function () {
    $agent = User::factory()->create();
    $form = publishedForm($agent, [question('rating', 'single_choice', ['options' => [['id' => 'good', 'label' => 'Good']], 'required' => true]), question('comments', 'long_text')]);

    $this->postJson("/api/v1/public/forms/{$form->token}/responses", ['answers' => ['rating' => 'good', 'comments' => 'Great service']])->assertCreated();

    expect(Lead::count())->toBe(0)
        ->and($form->responses()->sole())->status->toBe(FormResponseStatus::New)->answers->toBe(['rating' => 'good', 'comments' => 'Great service']);
});

it('validates answers against the published form', function (array $answers, string $error) {
    $agent = User::factory()->create();
    $form = publishedForm($agent, [
        question('name', 'short_text', ['link' => 'name', 'required' => true]),
        question('email', 'email', ['link' => 'email']),
        question('phone', 'phone', ['link' => 'phone']),
        question('topics', 'multiple_choice', ['options' => [['id' => 'a', 'label' => 'A'], ['id' => 'b', 'label' => 'B']]]),
        question('agree', 'consent', ['required' => true]),
    ], addToLeads: true);

    $this->postJson("/api/v1/public/forms/{$form->token}/responses", ['answers' => [
        'name' => 'Juan', 'email' => 'juan@example.com', 'agree' => true, ...$answers,
    ]])->assertUnprocessable()->assertJsonValidationErrors($error);
})->with([
    'missing required' => [['name' => ''], 'answers.name'],
    'bad email' => [['email' => 'not-an-email'], 'answers.email'],
    'neither email nor phone' => [['email' => null], 'answers.email'],
    'unknown choice' => [['topics' => ['c']], 'answers.topics.0'],
    'consent not given' => [['agree' => false], 'answers.agree'],
    'unknown question' => [['extra' => 'x'], 'answers'],
]);

it('quietly drops responses that fill the honeypot', function () {
    $agent = User::factory()->create();
    contactFormOf($agent);

    $this->postJson('/api/v1/public/forms/form-token/responses', ['answers' => ['name' => 'Bot', 'email' => 'bot@example.com'], 'website' => 'spam.example'])->assertCreated();

    expect(Lead::count())->toBe(0)->and(workspaceOf($agent)->formResponses()->count())->toBe(0);
});

it('rate limits each visitor per form by their own IP', function () {
    $agent = User::factory()->create();
    contactFormOf($agent);
    $answers = ['answers' => ['name' => 'Juan', 'email' => 'juan@example.com']];

    foreach (range(1, 5) as $attempt) {
        $this->postJson('/api/v1/public/forms/form-token/responses', $answers)->assertCreated();
    }

    $this->postJson('/api/v1/public/forms/form-token/responses', $answers)->assertTooManyRequests();
    $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.2'])->postJson('/api/v1/public/forms/form-token/responses', $answers)->assertCreated();
});

it('counts each form’s responses and the ones still new', function () {
    $agent = User::factory()->create();
    $form = publishedForm($agent, [question('comments', 'long_text', ['required' => true])]);

    foreach (['One', 'Two', 'Three'] as $comment) {
        $this->postJson("/api/v1/public/forms/{$form->token}/responses", ['answers' => ['comments' => $comment]])->assertCreated();
    }
    $form->responses()->oldest()->first()->update(['status' => FormResponseStatus::Dismissed]);

    $listed = collect($this->actingAs($agent)->getJson(ws($agent).'/forms')->assertOk()->json('data'))->firstWhere('id', $form->id);

    expect($listed)->responses_count->toBe(3)->new_responses_count->toBe(2);
});

it('switches the default form, and won’t delete the default', function () {
    $agent = User::factory()->create();
    $contact = contactFormOf($agent);
    $survey = $this->actingAs($agent)->postJson(ws($agent).'/forms', ['name' => 'Survey'])->json('data');

    $this->deleteJson(ws($agent)."/forms/{$contact->id}")->assertUnprocessable()->assertJsonValidationErrors('form');

    $this->postJson(ws($agent)."/forms/{$survey['id']}/default")->assertOk()->assertJsonPath('data.is_default', true);
    expect($contact->refresh()->is_default)->toBeFalse();

    $this->deleteJson(ws($agent)."/forms/{$contact->id}")->assertNoContent();
    expect(Form::count())->toBe(1);
});

it('gives a form a new link so the old one stops working', function () {
    $agent = User::factory()->create();
    $form = contactFormOf($agent);

    $token = $this->actingAs($agent)->postJson(ws($agent)."/forms/{$form->id}/token")->assertOk()->json('data.token');

    expect($token)->not->toBe('form-token');
    $this->getJson('/api/v1/public/forms/form-token')->assertNotFound();
    $this->getJson("/api/v1/public/forms/{$token}")->assertOk();
});

it('keeps each workspace’s forms to itself', function () {
    $agent = User::factory()->create();
    $form = contactFormOf(User::factory()->create());

    $this->actingAs($agent)->getJson(ws($agent)."/forms/{$form->id}")->assertNotFound();
    $this->patchJson(ws($agent)."/forms/{$form->id}", ['name' => 'Mine now'])->assertNotFound();
});

it('lists templates, field types and the details a field can link to', function () {
    $this->actingAs(User::factory()->create())->getJson('/api/v1/form-catalog')
        ->assertOk()
        ->assertJsonPath('data.templates.0.value', 'contact')
        ->assertJsonFragment(['value' => 'phone', 'label' => 'Phone', 'type' => 'phone', 'options' => [], 'aliases' => ImportField::Phone->headerNames()])
        ->assertJsonFragment(['value' => 'divider', 'label' => 'Divider', 'layout' => true, 'has_options' => false]);
});

it('fills in a lead who answers again instead of adding them twice', function () {
    $agent = User::factory()->create();
    contactFormOf($agent);
    $lead = Lead::factory()->for(workspaceOf($agent))->create(['name' => 'Gregoria de Jesus', 'email' => 'Gregoria@example.com', 'phone' => null, 'notes' => 'Met at the fair']);

    $this->postJson('/api/v1/public/forms/form-token/responses', ['answers' => [
        'name' => 'Gregoria', 'email' => 'gregoria@example.com', 'phone' => '+63 917 555 0100', 'notes' => 'Call me',
    ]])->assertCreated();

    expect(Lead::count())->toBe(1)
        ->and($lead->refresh())->phone->toBe('0917 555 0100')->notes->toBe('Met at the fair')->name->toBe('Gregoria de Jesus')
        ->and(workspaceOf($agent)->formResponses()->sole())->status->toBe(FormResponseStatus::Added)->lead_id->toBe($lead->id)
        ->and($lead->followUps()->sole()->title)->toBe('Reach out to Gregoria de Jesus: sent your form again');

    $notification = $agent->notifications()->sole();
    expect($notification->type)->toBe('form_response')
        ->and($notification->data['body'])->toBe('They’re already in your workspace; added their phone.')
        ->and($notification->data['subject'])->toMatchArray(['kind' => 'lead', 'id' => $lead->id]);
});

it('matches a client by phone, however it’s written, with nothing new to add', function () {
    $agent = User::factory()->create();
    contactFormOf($agent);
    $client = Client::factory()->for(workspaceOf($agent))->create(['name' => 'Juan Cruz', 'phone' => '0917 555 0100']);

    $this->postJson('/api/v1/public/forms/form-token/responses', ['answers' => ['name' => 'Juan', 'phone' => '+639175550100']])->assertCreated();

    expect(Lead::count())->toBe(0)
        ->and(workspaceOf($agent)->formResponses()->sole()->client_id)->toBe($client->id)
        ->and($agent->notifications()->sole()->data['body'])->toBe('They’re already in your workspace; nothing new to add.');
});

it('tells everyone about responses that don’t become leads', function () {
    $agent = User::factory()->create();
    $form = publishedForm($agent, [question('rating', 'short_text', ['required' => true])]);

    $this->postJson("/api/v1/public/forms/{$form->token}/responses", ['answers' => ['rating' => 'Great']])->assertCreated();

    $notification = $agent->notifications()->sole();
    expect($notification->type)->toBe('form_response')
        ->and($notification->data['title'])->toBe('New response to Survey')
        ->and($notification->data['body'])->toBe('Someone answered Survey.')
        ->and($notification->data['subject'])->toBe(['kind' => 'form', 'id' => $form->id, 'name' => 'Survey']);
});
