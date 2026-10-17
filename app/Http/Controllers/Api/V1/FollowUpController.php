<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\FollowUps\CompleteFollowUp;
use App\Actions\FollowUps\CreateFollowUp;
use App\Actions\FollowUps\DeleteFollowUp;
use App\Actions\FollowUps\ListFollowUps;
use App\Actions\FollowUps\ReopenFollowUp;
use App\Actions\FollowUps\SummarizeFollowUps;
use App\Actions\FollowUps\UpdateFollowUp;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\FollowUps\ListFollowUpsRequest;
use App\Http\Requests\Api\V1\FollowUps\StoreFollowUpRequest;
use App\Http\Resources\Api\V1\FollowUpResource;
use App\Models\FollowUp;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class FollowUpController extends Controller
{
    public function index(ListFollowUpsRequest $request, #[CurrentUser] User $agent, ListFollowUps $listFollowUps): AnonymousResourceCollection
    {
        return FollowUpResource::collection($listFollowUps->handle($agent, $request->toData()));
    }

    public function summary(#[CurrentUser] User $agent, SummarizeFollowUps $summarizeFollowUps): JsonResponse
    {
        return response()->json(['data' => $summarizeFollowUps->handle($agent)]);
    }

    public function store(StoreFollowUpRequest $request, Lead $lead, CreateFollowUp $createFollowUp): JsonResponse
    {
        Gate::authorize('update', $lead);

        return FollowUpResource::make($createFollowUp->handle($lead, $request->toData())->load('lead'))
            ->response()
            ->setStatusCode(201);
    }

    public function update(StoreFollowUpRequest $request, FollowUp $followUp, UpdateFollowUp $updateFollowUp): FollowUpResource
    {
        Gate::authorize('update', $followUp);

        return FollowUpResource::make($updateFollowUp->handle($followUp, $request->toData())->load('lead'));
    }

    public function complete(FollowUp $followUp, #[CurrentUser] User $agent, CompleteFollowUp $completeFollowUp): FollowUpResource
    {
        Gate::authorize('update', $followUp);

        return FollowUpResource::make($completeFollowUp->handle($followUp, $agent)->load('lead'));
    }

    public function reopen(FollowUp $followUp, ReopenFollowUp $reopenFollowUp): FollowUpResource
    {
        Gate::authorize('update', $followUp);

        return FollowUpResource::make($reopenFollowUp->handle($followUp)->load('lead'));
    }

    public function destroy(FollowUp $followUp, DeleteFollowUp $deleteFollowUp): Response
    {
        Gate::authorize('delete', $followUp);

        $deleteFollowUp->handle($followUp);

        return response()->noContent();
    }
}
