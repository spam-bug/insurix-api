<?php

declare(strict_types=1);

use App\Actions\Premium\CreatePremiumLink;
use App\DataTransferObject\Premium\PremiumLinkData;
use App\Models\PremiumLink;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Testing\TestResponse;

function premiumLink(array $attributes = []): PremiumLink
{
    return app(CreatePremiumLink::class)->handle(PremiumLinkData::fromArray(['days' => 30, ...$attributes]));
}

function claim(PremiumLink $link, array $payload = []): TestResponse
{
    return test()->postJson("/api/v1/premium-links/{$link->token}/claims", $payload);
}

it('shows what a link gives to anyone, without its email', function () {
    $link = premiumLink(['email' => 'beta@example.com']);

    $this->getJson("/api/v1/public/premium-links/{$link->token}")
        ->assertOk()
        ->assertExactJson(['data' => [
            'days' => 30,
            'state' => ['value' => 'available', 'label' => 'Available'],
            'for_one_account' => true,
            'valid_until' => null,
        ]]);
});

it('gives the owner’s workspace Premium for the link’s days', function () {
    $this->freezeTime();
    $owner = User::factory()->create();

    $this->actingAs($owner);
    claim(premiumLink())
        ->assertCreated()
        ->assertJsonPath('data.id', workspaceOf($owner)->id)
        ->assertJsonPath('data.plan.value', 'premium')
        ->assertJsonPath('data.plan_ends_at', now()->addDays(30)->toIso8601String());

    expect(workspaceOf($owner)->premiumGrants()->sole()->user_id)->toBe($owner->id);
});

it('adds the days on top of Premium that is still running', function () {
    $this->freezeTime();
    $owner = User::factory()->create();
    workspaceOf($owner)->forceFill(['plan' => 'premium', 'plan_ends_at' => now()->addDays(10)])->save();

    $this->actingAs($owner);
    claim(premiumLink())->assertJsonPath('data.plan_ends_at', now()->addDays(40)->toIso8601String());
});

it('falls back to Free once Premium ends', function () {
    $owner = User::factory()->create();
    workspaceOf($owner)->forceFill(['plan' => 'premium', 'plan_ends_at' => now()->subDay()])->save();

    $this->actingAs($owner)->getJson(ws($owner))
        ->assertJsonPath('data.plan.value', 'free')
        ->assertJsonPath('data.plan_ends_at', null);
});

it('never gives Premium to a workspace the user was only invited into', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    workspaceOf($owner)->users()->attach($member, ['role' => 'member']);

    $this->actingAs($member);
    claim(premiumLink(), ['workspace_id' => workspaceOf($owner)->id])->assertUnprocessable()->assertJsonValidationErrors('workspace_id');

    expect(workspaceOf($owner)->currentPlan()->value)->toBe('free');
});

it('creates their own workspace for someone who owns none', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create(['name' => 'Ben']);
    workspaceOf($member)->delete();
    workspaceOf($owner)->users()->attach($member, ['role' => 'member']);

    $this->actingAs($member);
    claim(premiumLink())
        ->assertCreated()
        ->assertJsonPath('data.name', "Ben's workspace")
        ->assertJsonPath('data.role.value', 'owner')
        ->assertJsonPath('data.plan.value', 'premium');

    expect(workspaceOf($owner)->currentPlan()->value)->toBe('free');
});

it('asks which workspace when the user owns several', function () {
    $owner = User::factory()->create();
    $second = Workspace::factory()->ownedBy($owner)->create();
    $link = premiumLink();

    $this->actingAs($owner);
    claim($link)->assertUnprocessable()->assertJsonValidationErrors('workspace_id');
    claim($link, ['workspace_id' => $second->id])->assertCreated()->assertJsonPath('data.id', $second->id);
});

it('refuses expired, used up and already claimed links', function () {
    $owner = User::factory()->create();
    $this->actingAs($owner);

    claim(premiumLink(['valid_until' => now()->subDay()]))->assertUnprocessable()->assertJsonPath('errors.link.0', 'This link has expired.');

    $link = premiumLink(['max_claims' => 2]);
    claim($link)->assertCreated();
    claim($link)->assertUnprocessable()->assertJsonPath('errors.link.0', 'This workspace has already claimed this link.');

    $this->actingAs(User::factory()->create());
    claim($link)->assertCreated();

    $this->actingAs(User::factory()->create());
    claim($link)->assertUnprocessable()->assertJsonPath('errors.link.0', 'This link has already been used.');
});

it('only lets the named account claim a link made for one email', function () {
    $link = premiumLink(['email' => 'Beta@example.com']);

    $this->actingAs(User::factory()->create());
    claim($link)->assertForbidden();

    $this->actingAs(User::factory()->create(['email' => 'beta@example.com']));
    claim($link)->assertCreated();
});

it('needs a signed-in user to claim', function () {
    claim(premiumLink())->assertUnauthorized();
});
