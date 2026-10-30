<?php

declare(strict_types=1);

use App\Models\Form;
use App\Models\User;
use App\Support\FormTheme;

function premiumTheme(): array
{
    return [...FormTheme::defaults(), 'mode' => 'dark', 'accent' => '#0f766e', 'font' => 'serif', 'background' => 'mint', 'hide_branding' => true];
}

/** The contact form with this theme saved to its draft and published. */
function themedForm(User $agent, array $theme): Form
{
    $form = contactFormOf($agent);
    $draft = $form->draft;
    $draft['theme'] = $theme;

    test()->actingAs($agent)->patchJson(ws($agent)."/forms/{$form->id}", ['draft' => $draft])->assertOk();
    test()->postJson(ws($agent)."/forms/{$form->id}/publication")->assertOk();

    return $form->refresh();
}

it('starts forms with the default theme and saves changes to it', function () {
    $agent = User::factory()->create();
    $form = $this->actingAs($agent)->postJson(ws($agent).'/forms', ['name' => 'Survey'])
        ->assertJsonPath('data.draft.theme', FormTheme::defaults())
        ->json('data');

    $draft = $form['draft'];
    $draft['theme'] = premiumTheme();
    $this->patchJson(ws($agent)."/forms/{$form['id']}", ['draft' => $draft])->assertOk()->assertJsonPath('data.draft.theme.accent', '#0f766e');
});

it('rejects themes it doesn’t know', function (array $changes, string $error) {
    $agent = User::factory()->create();
    $form = contactFormOf($agent);
    $draft = $form->draft;
    $draft['theme'] = [...FormTheme::defaults(), ...$changes];

    $this->actingAs($agent)->patchJson(ws($agent)."/forms/{$form->id}", ['draft' => $draft])->assertUnprocessable()->assertJsonValidationErrors($error);
})->with([
    'colour name' => [['accent' => 'red'], 'draft.theme.accent'],
    'css injection' => [['accent' => '#fff;background:url(x)'], 'draft.theme.accent'],
    'unknown mode' => [['mode' => 'sepia'], 'draft.theme.mode'],
    'unknown background' => [['background' => 'stripes'], 'draft.theme.background'],
]);

it('shows Free workspaces’ forms without the Premium theme settings', function () {
    $agent = User::factory()->create();
    themedForm($agent, premiumTheme());

    $this->getJson('/api/v1/public/forms/form-token')
        ->assertOk()
        ->assertJsonPath('data.form.document.theme', [...premiumTheme(), 'font' => 'default', 'background' => 'none', 'hide_branding' => false]);
});

it('shows Premium workspaces’ forms with their whole theme', function () {
    $agent = User::factory()->create();
    workspaceOf($agent)->forceFill(['plan' => 'premium'])->save();
    themedForm($agent, premiumTheme());

    $this->getJson('/api/v1/public/forms/form-token')->assertOk()->assertJsonPath('data.form.document.theme', premiumTheme());
});

it('fills in a missing theme with the defaults', function () {
    $agent = User::factory()->create();
    $form = contactFormOf($agent);
    $published = $form->published;
    unset($published['theme']);
    $form->update(['published' => $published]);

    $this->getJson('/api/v1/public/forms/form-token')->assertJsonPath('data.form.document.theme', FormTheme::defaults());
});

it('previews the draft through a short-lived link, never the published form', function () {
    $agent = User::factory()->create();
    $form = contactFormOf($agent);
    $draft = $form->draft;
    $draft['settings']['title'] = 'Changed but not published';
    $this->actingAs($agent)->patchJson(ws($agent)."/forms/{$form->id}", ['draft' => $draft])->assertOk();

    $token = $this->postJson(ws($agent)."/forms/{$form->id}/preview")->assertCreated()->json('data.token');

    $this->getJson("/api/v1/public/form-previews/{$token}")
        ->assertOk()
        ->assertJsonPath('data.form.token', 'form-token')
        ->assertJsonPath('data.form.document.settings.title', 'Changed but not published');
    $this->getJson('/api/v1/public/forms/form-token')->assertJsonPath('data.form.document.settings.title', 'Get in touch');

    $this->travel(31)->minutes();
    $this->getJson("/api/v1/public/form-previews/{$token}")->assertNotFound();
    $this->getJson('/api/v1/public/form-previews/made-up')->assertNotFound();
});
