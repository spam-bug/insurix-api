<?php

declare(strict_types=1);

use App\Enums\LeadActivityType;
use App\Models\Client;
use App\Models\Lead;
use App\Models\Note;
use App\Models\Policy;
use App\Models\User;
use App\Notifications\MentionedNotification;
use Illuminate\Support\Facades\Notification;

function noteTeammate(User $owner, string $name = 'Ana Reyes'): User
{
    $member = User::factory()->create(['name' => $name]);
    workspaceOf($owner)->users()->attach($member, ['role' => 'member']);

    return $member;
}

it('writes a free note', function () {
    $agent = User::factory()->create(['name' => 'Jane Agent']);

    $this->actingAs($agent)
        ->postJson(ws($agent).'/notes', ['body' => 'Team meeting on Friday.'])
        ->assertCreated()
        ->assertJsonPath('data.body', 'Team meeting on Friday.')
        ->assertJsonPath('data.link', null)
        ->assertJsonPath('data.author.name', 'Jane Agent');
});

it('keeps a policy note with its client and lead, and shows it in the lead timeline', function () {
    $agent = User::factory()->create();
    $lead = Lead::factory()->for(workspaceOf($agent))->create();
    $client = Client::factory()->for(workspaceOf($agent))->create(['lead_id' => $lead->id]);
    $policy = Policy::factory()->for($client)->create(['product_name' => 'Life Plus']);

    $this->actingAs($agent)
        ->postJson(ws($agent).'/notes', ['policy_id' => $policy->id, 'body' => 'Wants to add a rider.'])
        ->assertCreated()
        ->assertJsonPath('data.link', ['kind' => 'policy', 'id' => $policy->id, 'name' => 'Life Plus', 'client' => $client->name]);

    expect(Note::sole())->lead_id->toBe($lead->id)->client_id->toBe($client->id);

    $this->getJson(ws($agent)."/leads/{$lead->id}/activities")
        ->assertJsonPath('data.0.type', 'note')
        ->assertJsonPath('data.0.body', 'Wants to add a rider.')
        ->assertJsonPath('data.0.note_id', Note::sole()->id);
});

it('shows a note on a client added directly in their history', function () {
    $agent = User::factory()->create();
    $client = Client::factory()->for(workspaceOf($agent))->create();

    $this->actingAs($agent)->postJson(ws($agent).'/notes', ['client_id' => $client->id, 'body' => 'Prefers calls after 6.'])->assertCreated();

    $this->getJson(ws($agent)."/clients/{$client->id}/history")
        ->assertJsonPath('data.0.source', 'note')
        ->assertJsonPath('data.0.body', 'Prefers calls after 6.');
});

it('links a note to one record of the workspace at most', function () {
    $agent = User::factory()->create();
    $lead = Lead::factory()->for(workspaceOf($agent))->create();
    $client = Client::factory()->for(workspaceOf($agent))->create();
    $elsewhere = Lead::factory()->create();

    $this->actingAs($agent)
        ->postJson(ws($agent).'/notes', ['lead_id' => $lead->id, 'client_id' => $client->id, 'body' => 'Hi'])
        ->assertJsonValidationErrors(['lead_id', 'client_id']);
    $this->postJson(ws($agent).'/notes', ['lead_id' => $elsewhere->id, 'body' => 'Hi'])
        ->assertJsonValidationErrors('lead_id');
});

it('moves a lead’s notes to the client on promotion', function () {
    $agent = User::factory()->create();
    $lead = Lead::factory()->for(workspaceOf($agent))->create();
    $this->actingAs($agent)->postJson(ws($agent).'/notes', ['lead_id' => $lead->id, 'body' => 'Ready to sign.'])->assertCreated();

    $this->postJson(ws($agent)."/leads/{$lead->id}/promote", [])->assertSuccessful();

    expect(Note::sole()->client_id)->toBe($lead->client->id);
});

it('lists pinned notes first, and filters by link, author and text', function () {
    $agent = User::factory()->create();
    $ana = noteTeammate($agent);
    $lead = Lead::factory()->for(workspaceOf($agent))->create(['name' => 'Juan Cruz']);
    $free = Note::factory()->for(workspaceOf($agent))->for($agent)->create(['body' => 'Office closed Monday', 'created_at' => now()->subDay()]);
    $leadNote = Note::factory()->for(workspaceOf($agent))->for($ana)->create(['lead_id' => $lead->id, 'body' => 'Called twice']);
    $pinned = Note::factory()->for(workspaceOf($agent))->for($ana)->create(['body' => 'Commission rates changed', 'pinned_at' => now(), 'created_at' => now()->subWeek()]);
    Note::factory()->create();

    $this->actingAs($agent)->getJson(ws($agent).'/notes')
        ->assertJsonPath('data.*.id', [$pinned->id, $leadNote->id, $free->id]);
    $this->getJson(ws($agent).'/notes?link=lead')->assertJsonPath('data.*.id', [$leadNote->id]);
    $this->getJson(ws($agent).'/notes?link=none')->assertJsonPath('data.*.id', [$pinned->id, $free->id]);
    $this->getJson(ws($agent).'/notes?mine=1')->assertJsonPath('data.*.id', [$free->id]);
    $this->getJson(ws($agent).'/notes?search=juan')->assertJsonPath('data.*.id', [$leadNote->id]);
});

it('lets only the author rewrite a note, telling only people newly mentioned', function () {
    Notification::fake();
    $agent = User::factory()->create();
    $ana = noteTeammate($agent);
    $ben = noteTeammate($agent, 'Ben Santos');
    $this->actingAs($agent)->postJson(ws($agent).'/notes', ['body' => "@[Ana](user:{$ana->id}) see this"])->assertCreated();
    $note = Note::sole();

    $this->putJson(ws($agent)."/notes/{$note->id}", ['body' => "@[Ana](user:{$ana->id}) and @[Ben](user:{$ben->id}) see this"])
        ->assertOk()
        ->assertJsonPath('data.body', "@[Ana](user:{$ana->id}) and @[Ben](user:{$ben->id}) see this");

    Notification::assertSentToTimes($ana, MentionedNotification::class, 1);
    Notification::assertSentToTimes($ben, MentionedNotification::class, 1);
    expect($note->mentions()->count())->toBe(2);

    $this->actingAs($ana)->putJson(ws($agent)."/notes/{$note->id}", ['body' => 'Mine now'])->assertForbidden();
});

it('lets the author or the owner delete a note, taking it out of the lead timeline', function () {
    $owner = User::factory()->create();
    $ana = noteTeammate($owner);
    $ben = noteTeammate($owner, 'Ben Santos');
    $lead = Lead::factory()->for(workspaceOf($owner))->create();
    $this->actingAs($ana)->postJson(ws($owner).'/notes', ['lead_id' => $lead->id, 'body' => 'Called.'])->assertCreated();
    $note = Note::sole();

    $this->actingAs($ben)->deleteJson(ws($owner)."/notes/{$note->id}")->assertForbidden();
    $this->actingAs($owner)->deleteJson(ws($owner)."/notes/{$note->id}")->assertNoContent();

    expect(Note::count())->toBe(0)
        ->and($lead->activities()->where('type', LeadActivityType::Note)->count())->toBe(0);
});

it('pins and unpins a note for the whole workspace', function () {
    $agent = User::factory()->create();
    $ana = noteTeammate($agent);
    $note = Note::factory()->for(workspaceOf($agent))->for($agent)->create();

    $this->actingAs($ana)->postJson(ws($agent)."/notes/{$note->id}/pin")->assertOk()->assertJsonPath('data.pinned_at', fn ($value) => $value !== null);
    $this->deleteJson(ws($agent)."/notes/{$note->id}/pin")->assertOk()->assertJsonPath('data.pinned_at', null);
});

it('opens one note', function () {
    $agent = User::factory()->create();
    $note = Note::factory()->for(workspaceOf($agent))->for($agent)->create(['body' => 'Office closed Monday']);

    $this->actingAs($agent)->getJson(ws($agent)."/notes/{$note->id}")->assertOk()->assertJsonPath('data.body', 'Office closed Monday');
});

it('hides notes of other workspaces', function () {
    $agent = User::factory()->create();
    $note = Note::factory()->create();

    $this->actingAs($agent)->getJson(ws($agent)."/notes/{$note->id}")->assertNotFound();
    $this->putJson(ws($agent)."/notes/{$note->id}", ['body' => 'Hi'])->assertNotFound();
    $this->deleteJson(ws($agent)."/notes/{$note->id}")->assertNotFound();
});
