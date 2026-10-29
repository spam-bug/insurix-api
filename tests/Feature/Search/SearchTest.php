<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Document;
use App\Models\Lead;
use App\Models\Note;
use App\Models\Policy;
use App\Models\User;

it('finds leads, clients, policies, documents and notes in one go', function () {
    $agent = User::factory()->create();
    $workspace = workspaceOf($agent);
    $lead = Lead::factory()->for($workspace)->create(['name' => 'Juan Santos', 'email' => 'juan@example.com']);
    $client = Client::factory()->for($workspace)->create(['name' => 'Maria Santos', 'email' => null, 'phone' => '0917 000 0000']);
    $policy = Policy::factory()->for($client)->create(['product_name' => 'Life Plus', 'policy_number' => 'LP-1']);
    $document = Document::factory()->for($workspace)->create(['client_id' => $client->id, 'name' => 'Santos family ID']);
    $note = Note::factory()->for($workspace)->for($agent)->create(['body' => 'Call the Santos family on Friday']);

    $this->actingAs($agent)
        ->getJson(ws($agent).'/search?q=santos')
        ->assertOk()
        ->assertJsonPath('data.leads', [['id' => $lead->id, 'title' => 'Juan Santos', 'subtitle' => 'juan@example.com']])
        ->assertJsonPath('data.clients', [['id' => $client->id, 'title' => 'Maria Santos', 'subtitle' => '0917 000 0000']])
        ->assertJsonPath('data.policies', [['id' => $policy->id, 'title' => 'Life Plus', 'subtitle' => 'Maria Santos · LP-1']])
        ->assertJsonPath('data.documents', [['id' => $document->id, 'title' => 'Santos family ID', 'subtitle' => 'Maria Santos', 'client_id' => $client->id]])
        ->assertJsonPath('data.notes', [['id' => $note->id, 'title' => 'Call the Santos family on Friday', 'subtitle' => null]]);
});

it('finds a promoted lead as the client only', function () {
    $agent = User::factory()->create();
    $lead = Lead::factory()->for(workspaceOf($agent))->create(['name' => 'Ana Reyes']);
    Client::factory()->for(workspaceOf($agent))->create(['name' => 'Ana Reyes', 'lead_id' => $lead->id]);

    $this->actingAs($agent)
        ->getJson(ws($agent).'/search?q=ana')
        ->assertJsonCount(0, 'data.leads')
        ->assertJsonCount(1, 'data.clients');
});

it('searches only the open workspace, and needs two characters', function () {
    $agent = User::factory()->create();
    Lead::factory()->create(['name' => 'Juan Santos']);

    $this->actingAs($agent)->getJson(ws($agent).'/search?q=santos')->assertJsonCount(0, 'data.leads');
    $this->getJson(ws($agent).'/search?q=s')->assertJsonValidationErrors('q');
});
