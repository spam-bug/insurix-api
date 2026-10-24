<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\FollowUps\DeleteFollowUp;
use App\Actions\FollowUps\ListFollowUps;
use App\Actions\FollowUps\UpdateFollowUp;
use App\DataTransferObject\FollowUps\FollowUpData;
use App\DataTransferObject\FollowUps\FollowUpFiltersData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\FollowUps\ListFollowUpsRequest;
use App\Http\Requests\Api\V1\FollowUps\UpdateFollowUpRequest;
use App\Http\Resources\Api\V1\FollowUpResource;
use App\Models\FollowUp;
use App\Models\Workspace;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class FollowUpController extends Controller
{
    public function index(ListFollowUpsRequest $request, Workspace $workspace, ListFollowUps $listFollowUps): AnonymousResourceCollection
    {
        return FollowUpResource::collection($listFollowUps->handle($workspace, FollowUpFiltersData::fromArray($request->validated())));
    }

    public function update(UpdateFollowUpRequest $request, FollowUp $followUp, UpdateFollowUp $updateFollowUp): FollowUpResource
    {
        return FollowUpResource::make($updateFollowUp->handle($followUp, FollowUpData::fromArray($request->validated()))->load(FollowUpResource::RELATIONS));
    }

    public function destroy(FollowUp $followUp, DeleteFollowUp $deleteFollowUp): Response
    {
        $deleteFollowUp->handle($followUp);

        return response()->noContent();
    }
}
