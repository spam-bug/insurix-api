<?php

declare(strict_types=1);

use App\Actions\Leads\ListLeadActivities;
use App\Actions\Notes\ListNotes;
use App\Actions\ServiceRequests\ListServiceRequests;
use App\DataTransferObject\Notes\NoteFiltersData;
use App\DataTransferObject\ServiceRequests\ServiceRequestFiltersData;
use App\Models\Client;
use App\Models\Lead;
use App\Models\ServiceRequest;
use App\Models\User;

/**
 * Redis reads cached values back with only the classes in cache.serializable_classes; the array
 * cache the tests use skips that, so a missing model would only fail in the browser.
 */
function readBackFromCache(mixed $value): mixed
{
    return unserialize(serialize($value), ['allowed_classes' => config('cache.serializable_classes')]);
}

it('reads cached notes, lead activities and service requests back whole', function () {
    $agent = User::factory()->create();
    $workspace = workspaceOf($agent);
    $lead = Lead::factory()->for($workspace)->create();
    ServiceRequest::factory()->for(Client::factory()->for($workspace))->create(['title' => 'Change beneficiary']);
    $this->actingAs($agent)->postJson(ws($agent).'/notes', ['body' => 'Prefers calls after 6 PM', 'lead_id' => $lead->id])->assertCreated();

    $notes = readBackFromCache(app(ListNotes::class)->handle($workspace, NoteFiltersData::fromArray([]), $agent));
    $activities = readBackFromCache(app(ListLeadActivities::class)->handle($lead));
    $requests = readBackFromCache(app(ListServiceRequests::class)->handle($workspace, ServiceRequestFiltersData::fromArray([])));

    expect($notes->items()[0]->body)->toBe('Prefers calls after 6 PM')
        ->and($notes->items()[0]->user->name)->toBe($agent->name)
        ->and($activities->items()[0]->note->body)->toBe('Prefers calls after 6 PM')
        ->and($requests->items()[0]->title)->toBe('Change beneficiary')
        ->and($requests->items()[0]->client->name)->not->toBeEmpty();
});
