<?php

use App\Enums\LeadActivityType;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\User;

it('creates the agent’s form token on first request and keeps it', function () {
    $agent = User::factory()->create();

    $token = $this->actingAs($agent)->getJson(route('api.v1.lead-form.show'))->assertOk()->json('data.token');

    expect($token)->toHaveLength(40)
        ->and($agent->fresh()->lead_form_token)->toBe($token);

    $this->getJson(route('api.v1.lead-form.show'))->assertJsonPath('data.token', $token);
});

it('regenerates the token so old links stop working', function () {
    $agent = User::factory()->create(['lead_form_token' => 'old-token']);

    $newToken = $this->actingAs($agent)->postJson(route('api.v1.lead-form.regenerate'))->assertOk()->json('data.token');

    expect($newToken)->not->toBe('old-token');
    $this->getJson(route('api.v1.public.lead-forms.show', 'old-token'))->assertNotFound();
    $this->getJson(route('api.v1.public.lead-forms.show', $newToken))->assertOk();
});

it('shows only the agent’s name on the public form', function () {
    User::factory()->create(['name' => 'Jane Agent', 'email' => 'jane@example.com', 'lead_form_token' => 'abc123']);

    $this->getJson(route('api.v1.public.lead-forms.show', 'abc123'))
        ->assertOk()
        ->assertExactJson(['data' => ['agent' => ['name' => 'Jane Agent']]]);
});

it('turns a public submission into a prospect for that agent', function () {
    $agent = User::factory()->create(['lead_form_token' => 'abc123']);

    $this->postJson(route('api.v1.public.lead-forms.submit', 'abc123'), [
        'name' => 'Maria Santos',
        'email' => 'Maria@Example.com',
        'interest' => 'health',
        'message' => 'Family of four, looking for health coverage.',
    ])->assertCreated()->assertJson(['data' => ['submitted' => true]]);

    $lead = $agent->leads()->sole();
    expect($lead)
        ->name->toBe('Maria Santos')
        ->email->toBe('maria@example.com')
        ->source->toBe(LeadSource::ShareableForm)
        ->status->toBe(LeadStatus::Prospect)
        ->notes->toBe('Family of four, looking for health coverage.')
        ->and($lead->activities()->sole())
        ->type->toBe(LeadActivityType::Created)
        ->user_id->toBeNull();
});

it('validates public submissions', function () {
    User::factory()->create(['lead_form_token' => 'abc123']);

    $this->postJson(route('api.v1.public.lead-forms.submit', 'abc123'), ['name' => ''])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'email', 'phone']);

    expect(Lead::count())->toBe(0);
});

it('quietly drops submissions that fill the honeypot', function () {
    User::factory()->create(['lead_form_token' => 'abc123']);

    $this->postJson(route('api.v1.public.lead-forms.submit', 'abc123'), [
        'name' => 'Bot',
        'email' => 'bot@example.com',
        'website' => 'https://spam.example',
    ])->assertCreated();

    expect(Lead::count())->toBe(0);
});

it('returns 404 for unknown form links', function () {
    $this->postJson(route('api.v1.public.lead-forms.submit', 'nope'), ['name' => 'Ana', 'phone' => '123'])->assertNotFound();
});

it('rate limits each visitor per form', function () {
    User::factory()->create(['lead_form_token' => 'abc123']);
    $payload = ['name' => 'Ana', 'phone' => '0917 555 0100'];

    foreach (range(1, 5) as $attempt) {
        $this->postJson(route('api.v1.public.lead-forms.submit', 'abc123'), $payload, ['X-Client-IP' => '203.0.113.7'])->assertCreated();
    }

    $this->postJson(route('api.v1.public.lead-forms.submit', 'abc123'), $payload, ['X-Client-IP' => '203.0.113.7'])->assertTooManyRequests();
    $this->postJson(route('api.v1.public.lead-forms.submit', 'abc123'), $payload, ['X-Client-IP' => '198.51.100.9'])->assertCreated();
});

it('never exposes the form token on the user resource', function () {
    $agent = User::factory()->create(['lead_form_token' => 'secret-token']);

    $this->actingAs($agent)->getJson(route('api.v1.auth.user'))->assertOk()->assertJsonMissingPath('data.lead_form_token');
});
