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
            'timezone' => 'Asia/Singapore',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');

    $this->putJson('/api/v1/account/profile', [
        'name' => '  Ana Reyes ',
        'email' => 'ana.reyes@example.com',
        'phone' => '0917 555 0101',
        'timezone' => 'Asia/Singapore',
    ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Ana Reyes')
        ->assertJsonPath('data.phone', '0917 555 0101')
        ->assertJsonPath('data.timezone', 'Asia/Singapore');
});

it('keeps emails unique and timezones real', function () {
    $agent = User::factory()->create();
    User::factory()->create(['email' => 'taken@example.com']);

    $this->actingAs($agent)
        ->putJson('/api/v1/account/profile', ['name' => 'Ana', 'email' => 'taken@example.com', 'timezone' => 'Mars/Olympus'])
        ->assertJsonValidationErrors(['email', 'timezone']);
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

    $this->postJson('/api/v1/account/logo', ['image' => UploadedFile::fake()->image('huge.jpg', 800, 800)->size(3000)])
        ->assertJsonValidationErrors(['image' => 'under 2 MB']);
});

it('sets the bio and logo shown on the shareable form', function () {
    $agent = User::factory()->create(['lead_form_token' => 'form-token']);
    $this->actingAs($agent);

    $this->putJson('/api/v1/account/lead-form', ['bio' => 'Helping families in Quezon City plan for the future.'])
        ->assertOk()
        ->assertJsonPath('data.bio', 'Helping families in Quezon City plan for the future.');
    $this->postJson('/api/v1/account/logo', ['image' => UploadedFile::fake()->image('logo.png', 300, 120)])->assertOk();

    $this->getJson('/api/v1/public/lead-forms/form-token')
        ->assertOk()
        ->assertJsonPath('data.agent.bio', 'Helping families in Quezon City plan for the future.')
        ->assertJsonPath('data.agent.logo_url', Storage::disk(User::UPLOADS_DISK)->url($agent->fresh()->logo_path));
});

it('includes the account details on the current user', function () {
    $agent = User::factory()->create(['phone' => '0917 000 0000', 'bio' => 'Hi']);

    $this->actingAs($agent)
        ->getJson('/api/v1/auth/user')
        ->assertJsonPath('data.phone', '0917 000 0000')
        ->assertJsonPath('data.bio', 'Hi')
        ->assertJsonPath('data.two_factor_enabled', false)
        ->assertJsonMissingPath('data.two_factor_secret');
});
