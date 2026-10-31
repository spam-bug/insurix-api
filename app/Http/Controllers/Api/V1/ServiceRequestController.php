<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\ServiceRequests\DeleteServiceRequest;
use App\Actions\ServiceRequests\ListServiceRequests;
use App\Actions\ServiceRequests\UpdateServiceRequest;
use App\DataTransferObject\ServiceRequests\ServiceRequestData;
use App\DataTransferObject\ServiceRequests\ServiceRequestFiltersData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ServiceRequests\ListServiceRequestsRequest;
use App\Http\Requests\Api\V1\ServiceRequests\UpdateServiceRequestRequest;
use App\Http\Resources\Api\V1\ServiceRequestResource;
use App\Models\ServiceRequest;
use App\Models\Workspace;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ServiceRequestController extends Controller
{
    public function index(ListServiceRequestsRequest $request, Workspace $workspace, ListServiceRequests $listServiceRequests): AnonymousResourceCollection
    {
        return ServiceRequestResource::collection($listServiceRequests->handle($workspace, ServiceRequestFiltersData::fromArray($request->validated())));
    }

    public function update(UpdateServiceRequestRequest $request, ServiceRequest $serviceRequest, UpdateServiceRequest $updateServiceRequest): ServiceRequestResource
    {
        return ServiceRequestResource::make($updateServiceRequest->handle($serviceRequest, ServiceRequestData::fromArray($request->validated()))->load(ServiceRequestResource::RELATIONS));
    }

    public function destroy(ServiceRequest $serviceRequest, DeleteServiceRequest $deleteServiceRequest): Response
    {
        $deleteServiceRequest->handle($serviceRequest);

        return response()->noContent();
    }
}
