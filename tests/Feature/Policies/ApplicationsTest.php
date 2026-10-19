<?php

use App\Enums\PolicyStatus;
use App\Enums\RenewalTerm;
use App\Models\Client;
use App\Models\Document;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Support\Carbon;

beforeEach(fn () => Carbon::setTestNow('2026-10-17 02:00:00'));

/**
 * A pending application recorded through the API, so it gets its timeline entry.
 */
function application(?User $agent = null): Policy
{
    $client = Client::factory()->for($agent ?? User::factory())->create();
    test()->actingAs($client->user)->postJson(route('api.v1.clients.policies.store', $client), [
        'product_name' => 'Life Plus',
        'type' => 'life',
        'status' => 'pending',
        'premium' => 2500,
        'payment_frequency' => 'monthly',
        'commission_rate' => 30,
        'renewal_term' => 'annual',
        'effective_date' => '2026-11-01',
    ])->assertCreated();

    return Policy::latest('id')->first();
}

it('starts a pending policy as a submitted application', function () {
    $policy = application();

    $this->getJson(route('api.v1.policies.show', $policy))
        ->assertOk()
        ->assertJsonPath('data.application_stage', ['value' => 'submitted', 'label' => 'Submitted'])
        ->assertJsonPath('data.requirements', [])
        ->assertJsonPath('data.events.0.type', 'recorded');
});

it('moves an application through its stages, keeping the timer when the stage stays', function () {
    $policy = application();

    Carbon::setTestNow(now()->addDays(3));
    $this->postJson(route('api.v1.policies.stage', $policy), ['stage' => 'underwriting'])
        ->assertOk()
        ->assertJsonPath('data.application_stage.value', 'underwriting')
        ->assertJsonPath('data.events.0.properties', ['from' => 'submitted', 'to' => 'underwriting']);
    $movedAt = $policy->fresh()->stage_changed_at;

    Carbon::setTestNow(now()->addDays(2));
    $this->postJson(route('api.v1.policies.stage', $policy), ['stage' => 'underwriting'])->assertOk();

    expect($policy->fresh()->stage_changed_at->equalTo($movedAt))->toBeTrue()
        ->and($policy->events()->count())->toBe(2);
});

it('keeps a checklist of requirements and ticks them off with documents', function () {
    $policy = application();
    $document = Document::factory()->for($policy->client)->create(['name' => 'Medical exam results']);
    $otherDocument = Document::factory()->create();

    $this->postJson(route('api.v1.policies.requirements.store', $policy), ['title' => 'Medical exam'])
        ->assertOk()
        ->assertJsonPath('data.application_stage.value', 'requirements_needed')
        ->assertJsonPath('data.requirements.0.title', 'Medical exam')
        ->assertJsonPath('data.requirements.0.received_on', null);
    $this->postJson(route('api.v1.policies.requirements.store', $policy), ['title' => 'Valid ID'])->assertOk();
    [$exam, $id] = $policy->requirements()->orderBy('id')->get()->all();

    $this->putJson(route('api.v1.requirements.update', $exam), ['received' => false, 'document_id' => $otherDocument->id])
        ->assertJsonValidationErrors(['document_id' => 'this client’s documents']);

    $this->putJson(route('api.v1.requirements.update', $exam), ['received' => false, 'document_id' => $document->id])
        ->assertOk()
        // Outstanding requirements come first.
        ->assertJsonPath('data.requirements.0.title', 'Valid ID')
        ->assertJsonPath('data.requirements.1.received_on', '2026-10-17')
        ->assertJsonPath('data.requirements.1.document', ['id' => $document->id, 'name' => 'Medical exam results']);

    $this->putJson(route('api.v1.requirements.update', $id), ['received' => true])->assertOk();
    $this->putJson(route('api.v1.requirements.update', $id), ['received' => false])->assertOk();
    expect($id->fresh()->received_on)->toBeNull();

    $this->deleteJson(route('api.v1.requirements.destroy', $id))->assertNoContent();
    expect($policy->requirements()->count())->toBe(1)
        ->and($policy->events()->pluck('type')->map->value->all())->toContain('requirement_added', 'requirement_received');
});

it('approves an application into force with its effective date and number', function () {
    $policy = application();
    $policy->update(['effective_date' => null]);

    $this->postJson(route('api.v1.policies.approve', $policy), [])->assertJsonValidationErrors(['effective_date' => 'takes effect']);

    $this->postJson(route('api.v1.policies.approve', $policy), ['effective_date' => '2026-11-15', 'policy_number' => 'LP-0001'])
        ->assertOk()
        ->assertJsonPath('data.status.value', 'in_force')
        ->assertJsonPath('data.application_stage', null)
        ->assertJsonPath('data.effective_date', '2026-11-15')
        ->assertJsonPath('data.policy_number', 'LP-0001')
        ->assertJsonPath('data.events.0.type', 'approved');

    $this->postJson(route('api.v1.policies.decline', $policy))->assertStatus(422);
});

it('declines an application with the reason on the timeline', function () {
    $policy = application();

    $this->postJson(route('api.v1.policies.decline', $policy), ['reason' => 'Pre-existing condition'])
        ->assertOk()
        ->assertJsonPath('data.status', ['value' => 'declined', 'label' => 'Declined'])
        ->assertJsonPath('data.events.0.properties.reason', 'Pre-existing condition');
});

it('flags an application stuck in one stage, with what is still outstanding', function () {
    $policy = application();
    $this->postJson(route('api.v1.policies.requirements.store', $policy), ['title' => 'Medical exam'])->assertOk();
    $this->postJson(route('api.v1.policies.requirements.store', $policy), ['title' => 'Valid ID'])->assertOk();

    Carbon::setTestNow(now()->addDays(20));

    $this->getJson(route('api.v1.policies.attention'))
        ->assertJsonPath('data.0.reasons.0.label', 'Requirements needed for 20 days with 2 requirements outstanding: check with the insurer');
    expect(collect($this->getJson(route('api.v1.insights.next-actions'))->json('data'))->pluck('code'))->toContain('stuck_applications');
});

it('keeps agents out of each other’s applications', function () {
    $policy = application();
    $requirement = $policy->requirements()->create(['title' => 'Medical exam']);
    $this->actingAs(User::factory()->create());

    $this->postJson(route('api.v1.policies.stage', $policy), ['stage' => 'underwriting'])->assertForbidden();
    $this->postJson(route('api.v1.policies.approve', $policy), ['effective_date' => '2026-11-01'])->assertForbidden();
    $this->postJson(route('api.v1.policies.requirements.store', $policy), ['title' => 'X'])->assertForbidden();
    $this->putJson(route('api.v1.requirements.update', $requirement), ['received' => true])->assertForbidden();
    $this->deleteJson(route('api.v1.requirements.destroy', $requirement))->assertForbidden();
});

it('only has a stage while pending', function () {
    $policy = Policy::factory()->status(PolicyStatus::Pending)->create(['renewal_term' => RenewalTerm::Custom]);
    expect($policy->application_stage?->value)->toBe('submitted');

    $policy->update(['status' => PolicyStatus::InForce]);
    expect($policy->fresh()->application_stage)->toBeNull();
});
