<?php

declare(strict_types=1);

use App\Models\User;

it('lists where the agent is signed in and marks this device', function () {
    $agent = User::factory()->create();
    $current = $agent->createToken('Chrome on Windows');
    $agent->createToken('Safari on iPhone');
    User::factory()->create()->createToken('Someone else');

    $this->withToken($current->plainTextToken)
        ->getJson(route('api.v1.account.sessions.index'))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.device', 'Chrome on Windows')
        ->assertJsonPath('data.0.current', true)
        ->assertJsonPath('data.1.current', false);
});

it('signs out one other device', function () {
    $agent = User::factory()->create();
    $current = $agent->createToken('Chrome on Windows');
    $phone = $agent->createToken('Safari on iPhone');
    $someoneElse = User::factory()->create()->createToken('Someone else');

    $this->withToken($current->plainTextToken)
        ->deleteJson(route('api.v1.account.sessions.destroy', $someoneElse->accessToken->id))
        ->assertNotFound();
    $this->deleteJson(route('api.v1.account.sessions.destroy', $current->accessToken->id))->assertNotFound();
    $this->deleteJson(route('api.v1.account.sessions.destroy', $phone->accessToken->id))->assertOk();

    expect($agent->tokens()->pluck('name')->all())->toBe(['Chrome on Windows']);
});

it('signs out every other device', function () {
    $agent = User::factory()->create();
    $current = $agent->createToken('Chrome on Windows');
    $agent->createToken('Safari on iPhone');
    $agent->createToken('Firefox on Mac');

    $this->withToken($current->plainTextToken)
        ->deleteJson(route('api.v1.account.sessions.destroy-others'))
        ->assertOk()
        ->assertJsonPath('data.revoked', 2);

    expect($agent->tokens()->count())->toBe(1);
});
