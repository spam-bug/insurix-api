<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Leads\CreateLead;
use App\Actions\Leads\DeleteLead;
use App\Actions\Leads\ListLeads;
use App\Actions\Leads\UpdateLead;
use App\Actions\Leads\UpdateLeadStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Leads\ListLeadsRequest;
use App\Http\Requests\Api\V1\Leads\StoreLeadRequest;
use App\Http\Requests\Api\V1\Leads\UpdateLeadRequest;
use App\Http\Requests\Api\V1\Leads\UpdateLeadStatusRequest;
use App\Http\Resources\Api\V1\LeadResource;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class LeadController extends Controller
{
    public function index(ListLeadsRequest $request, #[CurrentUser] User $agent, ListLeads $listLeads): AnonymousResourceCollection
    {
        return LeadResource::collection($listLeads->handle($agent, $request->toData()));
    }

    public function store(StoreLeadRequest $request, #[CurrentUser] User $agent, CreateLead $createLead): JsonResponse
    {
        return LeadResource::make($createLead->handle($agent, $request->toData()))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Lead $lead): LeadResource
    {
        Gate::authorize('view', $lead);

        return LeadResource::make($lead);
    }

    public function update(UpdateLeadRequest $request, Lead $lead, #[CurrentUser] User $agent, UpdateLead $updateLead): LeadResource
    {
        Gate::authorize('update', $lead);

        return LeadResource::make($updateLead->handle($lead, $request->toData(), $agent));
    }

    public function updateStatus(UpdateLeadStatusRequest $request, Lead $lead, #[CurrentUser] User $agent, UpdateLeadStatus $updateLeadStatus): LeadResource
    {
        Gate::authorize('update', $lead);

        return LeadResource::make($updateLeadStatus->handle($lead, $request->status(), $agent));
    }

    public function destroy(Lead $lead, DeleteLead $deleteLead): Response
    {
        Gate::authorize('delete', $lead);

        $deleteLead->handle($lead);

        return response()->noContent();
    }
}
