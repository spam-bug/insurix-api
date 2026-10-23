<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\DB;

it('lists where the agent is signed in and marks this device', function () {
    $agent = User::factory()->create();
    $current = storeSession($agent, 'Chrome on Windows', now()->timestamp);
    storeSession($agent, 'Safari on iPhone', now()->subHour()->timestamp);
    storeSession(User::factory()->create(), 'Someone else');

    useSession($current);
    $this->actingAs($agent)
        ->getJson('/api/v1/account/sessions')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.user_agent', 'Chrome on Windows')
        ->assertJsonPath('data.0.current', true)
        ->assertJsonPath('data.1.current', false);
});

it('signs out one other device', function () {
    $agent = User::factory()->create();
    $current = storeSession($agent, 'Chrome on Windows');
    $phone = storeSession($agent, 'Safari on iPhone');
    $someoneElse = storeSession(User::factory()->create(), 'Someone else');

    useSession($current);
    $this->actingAs($agent)->deleteJson("/api/v1/account/sessions/{$someoneElse}")->assertNotFound();
    $this->deleteJson("/api/v1/account/sessions/{$current}")->assertNotFound();
    $this->deleteJson("/api/v1/account/sessions/{$phone}")->assertOk();

    expect(DB::table('sessions')->where('user_id', $agent->id)->pluck('user_agent')->all())->toBe(['Chrome on Windows']);
});

it('signs out every other device', function () {
    $agent = User::factory()->create();
    $current = storeSession($agent, 'Chrome on Windows');
    storeSession($agent, 'Safari on iPhone');
    storeSession($agent, 'Firefox on Mac');

    useSession($current);
    $this->actingAs($agent)
        ->deleteJson('/api/v1/account/sessions')
        ->assertOk()
        ->assertJsonPath('data.revoked', 2);

    expect(DB::table('sessions')->where('user_id', $agent->id)->pluck('id')->all())->toBe([$current]);
});
