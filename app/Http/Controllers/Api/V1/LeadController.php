<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Leads\CreateLead;
use App\Actions\Leads\DeleteLead;
use App\Actions\Leads\ListLeads;
use App\Actions\Leads\UpdateLead;
use App\DataTransferObject\Leads\LeadData;
use App\DataTransferObject\Leads\LeadFiltersData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Leads\ListLeadsRequest;
use App\Http\Requests\Api\V1\Leads\StoreLeadRequest;
use App\Http\Requests\Api\V1\Leads\UpdateLeadRequest;
use App\Http\Resources\Api\V1\LeadResource;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class LeadController extends Controller
{
    public function index(ListLeadsRequest $request, #[CurrentUser] User $agent, ListLeads $listLeads): AnonymousResourceCollection
    {
        return LeadResource::collection($listLeads->handle($agent, LeadFiltersData::fromArray($request->validated())));
    }

    public function store(StoreLeadRequest $request, #[CurrentUser] User $agent, CreateLead $createLead): JsonResponse
    {
        return LeadResource::make($createLead->handle($agent, LeadData::fromArray($request->validated())))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Lead $lead): LeadResource
    {
        return LeadResource::make($lead);
    }

    public function update(UpdateLeadRequest $request, Lead $lead, #[CurrentUser] User $agent, UpdateLead $updateLead): LeadResource
    {
        return LeadResource::make($updateLead->handle($lead, LeadData::fromArray($request->validated()), $agent));
    }

    public function destroy(Lead $lead, DeleteLead $deleteLead): Response
    {
        $deleteLead->handle($lead);

        return response()->noContent();
    }
}
