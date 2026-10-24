<?php

declare(strict_types=1);

use App\Enums\LeadActivityType;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\User;

it('creates the workspace’s form token on first request and keeps it', function () {
    $agent = User::factory()->create();

    $token = $this->actingAs($agent)->getJson(ws($agent).'/lead-form')->assertOk()->json('data.token');

    expect($token)->toHaveLength(40)
        ->and(workspaceOf($agent)->fresh()->lead_form_token)->toBe($token);

    $this->getJson(ws($agent).'/lead-form')->assertJsonPath('data.token', $token);
});

it('regenerates the token so old links stop working', function () {
    $agent = withLeadForm(User::factory()->create(), 'old-token');

    $newToken = $this->actingAs($agent)->postJson(ws($agent).'/lead-form/regenerate')->assertOk()->json('data.token');

    expect($newToken)->not->toBe('old-token');
    $this->getJson('/api/v1/public/lead-forms/old-token')->assertNotFound();
    $this->getJson("/api/v1/public/lead-forms/{$newToken}")->assertOk();
});

it('shows only the workspace’s name on the public form', function () {
    $agent = withLeadForm(User::factory()->create(['name' => 'Jane Agent', 'email' => 'jane@example.com']), 'abc123');
    workspaceOf($agent)->update(['name' => 'Jane Agent Insurance']);

    $this->getJson('/api/v1/public/lead-forms/abc123')
        ->assertOk()
        ->assertExactJson(['data' => ['agent' => ['name' => 'Jane Agent Insurance', 'bio' => null, 'avatar_url' => null, 'logo_url' => null]]]);
});

it('turns a public submission into a prospect in that workspace', function () {
    $agent = withLeadForm(User::factory()->create(), 'abc123');

    $this->postJson('/api/v1/public/lead-forms/abc123', [
        'name' => 'Maria Santos',
        'email' => 'Maria@Example.com',
        'interest' => 'health',
        'message' => 'Family of four, looking for health coverage.',
    ])->assertCreated()->assertJson(['data' => ['submitted' => true]]);

    $lead = workspaceOf($agent)->leads()->sole();
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
    withLeadForm(User::factory()->create(), 'abc123');

    $this->postJson('/api/v1/public/lead-forms/abc123', ['name' => ''])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'email', 'phone']);

    expect(Lead::count())->toBe(0);
});

it('quietly drops submissions that fill the honeypot', function () {
    withLeadForm(User::factory()->create(), 'abc123');

    $this->postJson('/api/v1/public/lead-forms/abc123', [
        'name' => 'Bot',
        'email' => 'bot@example.com',
        'website' => 'https://spam.example',
    ])->assertCreated();

    expect(Lead::count())->toBe(0);
});

it('returns 404 for unknown form links', function () {
    $this->postJson('/api/v1/public/lead-forms/nope', ['name' => 'Ana', 'phone' => '123'])->assertNotFound();
});

it('rate limits each visitor per form by their own IP', function () {
    withLeadForm(User::factory()->create(), 'abc123');
    $payload = ['name' => 'Ana', 'phone' => '0917 555 0100'];
    $submitFrom = fn (string $ip, array $headers = []) => $this->withServerVariables(['REMOTE_ADDR' => $ip])
        ->postJson('/api/v1/public/lead-forms/abc123', $payload, $headers);

    foreach (range(1, 5) as $attempt) {
        $submitFrom('203.0.113.7')->assertCreated();
    }

    $submitFrom('203.0.113.7', ['X-Client-IP' => '192.0.2.1'])->assertTooManyRequests();
    $submitFrom('198.51.100.9')->assertCreated();
});

it('never exposes the form token on the user or workspace resources', function () {
    $agent = withLeadForm(User::factory()->create(), 'secret-token');

    $this->actingAs($agent)->getJson('/api/v1/auth/user')->assertOk()->assertJsonMissingPath('data.workspaces.0.lead_form_token');
    $this->getJson(ws($agent))->assertOk()->assertJsonMissingPath('data.lead_form_token');
});
