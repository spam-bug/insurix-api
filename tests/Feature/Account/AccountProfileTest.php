<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => Storage::fake(User::UPLOADS_DISK));

it('updates the agent’s profile', function () {
    $agent = User::factory()->create();

    $this->actingAs($agent)
        ->putJson('/api/v1/account/profile', [
            'name' => '  Ana Reyes ',
            'email' => 'Ana.Reyes@Example.com',
            'phone' => '0917 555 0101',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');

    $this->putJson('/api/v1/account/profile', [
        'name' => '  Ana Reyes ',
        'email' => 'ana.reyes@example.com',
        'phone' => '0917 555 0101',
    ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Ana Reyes')
        ->assertJsonPath('data.phone', '0917 555 0101');
});

it('keeps emails unique', function () {
    $agent = User::factory()->create();
    User::factory()->create(['email' => 'taken@example.com']);

    $this->actingAs($agent)
        ->putJson('/api/v1/account/profile', ['name' => 'Ana', 'email' => 'taken@example.com'])
        ->assertJsonValidationErrors(['email']);
});

it('uploads, replaces and removes the profile photo', function () {
    $agent = User::factory()->create();
    $this->actingAs($agent);

    $first = $this->postJson('/api/v1/account/photo', ['image' => UploadedFile::fake()->image('me.jpg', 400, 400)])->assertOk();
    $firstPath = $agent->fresh()->avatar_path;
    Storage::disk(User::UPLOADS_DISK)->assertExists($firstPath);
    expect($first->json('data.avatar_url'))->toContain($firstPath);

    $this->postJson('/api/v1/account/photo', ['image' => UploadedFile::fake()->image('me-again.png', 400, 400)])->assertOk();
    Storage::disk(User::UPLOADS_DISK)->assertMissing($firstPath);

    $this->deleteJson('/api/v1/account/photo')->assertOk()->assertJsonPath('data.avatar_url', null);
    expect($agent->fresh()->avatar_path)->toBeNull();
});

it('rejects files that are not small images', function () {
    $this->actingAs(User::factory()->create())
        ->postJson('/api/v1/account/photo', ['image' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf')])
        ->assertJsonValidationErrors('image');

    $this->postJson('/api/v1/account/photo', ['image' => UploadedFile::fake()->image('huge.jpg', 800, 800)->size(3000)])
        ->assertJsonValidationErrors(['image' => 'under 2 MB']);
});

it('includes the account details on the current user', function () {
    $agent = User::factory()->create(['phone' => '0917 000 0000']);

    $this->actingAs($agent)
        ->getJson('/api/v1/auth/user')
        ->assertJsonPath('data.phone', '0917 000 0000')
        ->assertJsonPath('data.workspaces.0.id', workspaceOf($agent)->id)
        ->assertJsonPath('data.workspaces.0.role.value', 'owner')
        ->assertJsonPath('data.two_factor_enabled', false)
        ->assertJsonMissingPath('data.two_factor_secret');
});
