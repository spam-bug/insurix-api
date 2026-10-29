<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\Mention;
use App\Models\User;
use App\Notifications\MentionedNotification;
use Illuminate\Support\Facades\Notification;

function memberNamed(User $owner, string $name): User
{
    $member = User::factory()->create(['name' => $name]);
    workspaceOf($owner)->users()->attach($member, ['role' => 'member']);

    return $member;
}

it('tells a mentioned teammate, with the note as plain text', function () {
    $agent = User::factory()->create(['name' => 'Jane Agent']);
    $ana = memberNamed($agent, 'Ana Reyes');
    $lead = Lead::factory()->for(workspaceOf($agent))->create(['name' => 'Juan Cruz']);

    $this->actingAs($agent)
        ->postJson(ws($agent).'/notes', ['lead_id' => $lead->id, 'body' => "@[Ana Reyes](user:{$ana->id}) can you call him back?"])
        ->assertCreated()
        ->assertJsonPath('data.body', "@[Ana Reyes](user:{$ana->id}) can you call him back?");

    $notification = $ana->notifications()->sole();
    expect($notification->type)->toBe('mention')
        ->and($notification->data['title'])->toBe('Jane Agent mentioned you')
        ->and($notification->data['body'])->toBe('@Ana Reyes can you call him back?')
        ->and($notification->data['subject'])->toBe(['kind' => 'lead', 'id' => $lead->id, 'name' => 'Juan Cruz'])
        ->and(Mention::sole())->user_id->toBe($ana->id)->mentioned_by->toBe($agent->id);
});

it('mentions each member once, and skips the author and people outside the workspace', function () {
    Notification::fake();
    $agent = User::factory()->create(['name' => 'Jane Agent']);
    $ana = memberNamed($agent, 'Ana Reyes');
    $ben = memberNamed($agent, 'Ben Santos');
    $outsider = User::factory()->create();
    $lead = Lead::factory()->for(workspaceOf($agent))->create();
    $body = "@[Ana](user:{$ana->id}) @[Ana](user:{$ana->id}) @[Ben](user:{$ben->id}) @[Me](user:{$agent->id}) @[Who](user:{$outsider->id})";

    $this->actingAs($agent)->postJson(ws($agent).'/notes', ['lead_id' => $lead->id, 'body' => $body])->assertCreated();

    expect(Mention::pluck('user_id')->sort()->values()->all())->toBe(collect([$ana->id, $ben->id])->sort()->values()->all());
    Notification::assertSentTo([$ana, $ben], MentionedNotification::class);
    Notification::assertSentToTimes($ana, MentionedNotification::class, 1);
    Notification::assertNotSentTo([$agent, $outsider], MentionedNotification::class);
});

it('sends nothing for a note without mentions', function () {
    Notification::fake();
    $agent = User::factory()->create();
    memberNamed($agent, 'Ana Reyes');
    $lead = Lead::factory()->for(workspaceOf($agent))->create();

    $this->actingAs($agent)->postJson(ws($agent).'/notes', ['lead_id' => $lead->id, 'body' => 'Email ana@example.com @Ana'])->assertCreated();

    expect(Mention::count())->toBe(0);
    Notification::assertNothingSent();
});

it('points a mention on a promoted lead to the client', function () {
    $agent = User::factory()->create();
    $ana = memberNamed($agent, 'Ana Reyes');
    $lead = Lead::factory()->for(workspaceOf($agent))->create();
    $client = Client::factory()->for(workspaceOf($agent))->create(['lead_id' => $lead->id, 'name' => 'Juan Cruz']);

    $this->actingAs($agent)->postJson(ws($agent).'/notes', ['lead_id' => $lead->id, 'body' => "@[Ana](user:{$ana->id}) renewal talk went well"])->assertCreated();

    expect($ana->notifications()->sole()->data['subject'])->toBe(['kind' => 'client', 'id' => $client->id, 'name' => 'Juan Cruz']);
});

it('suggests who worked on the lead first, then who the agent mentioned lately', function () {
    $agent = User::factory()->create();
    $ana = memberNamed($agent, 'Ana Reyes');
    $ben = memberNamed($agent, 'Ben Santos');
    $cora = memberNamed($agent, 'Cora Diaz');
    $lead = Lead::factory()->for(workspaceOf($agent))->create();
    $otherLead = Lead::factory()->for(workspaceOf($agent))->create();
    LeadActivity::factory()->for($lead)->create(['user_id' => $ben->id, 'created_at' => now()->subDay()]);
    LeadActivity::factory()->for($lead)->create(['user_id' => $ana->id, 'created_at' => now()->subHour()]);
    LeadActivity::factory()->for($lead)->create(['user_id' => $agent->id]);

    $this->actingAs($agent)->postJson(ws($agent).'/notes', ['lead_id' => $otherLead->id, 'body' => "@[Cora](user:{$cora->id}) and @[Ana](user:{$ana->id})"])->assertCreated();

    $this->getJson(ws($agent)."/leads/{$lead->id}/mention-suggestions")
        ->assertOk()
        ->assertExactJson(['data' => [$ana->id, $ben->id, $cora->id]]);
});

it('hides suggestions for a lead in another workspace', function () {
    $agent = User::factory()->create();
    $lead = Lead::factory()->create();

    $this->actingAs($agent)->getJson(ws($agent)."/leads/{$lead->id}/mention-suggestions")->assertNotFound();
});
