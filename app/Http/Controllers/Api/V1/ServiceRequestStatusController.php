<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\ServiceRequests\UpdateServiceRequestStatus;
use App\Enums\ServiceRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ServiceRequests\UpdateServiceRequestStatusRequest;
use App\Http\Resources\Api\V1\ServiceRequestResource;
use App\Models\ServiceRequest;

class ServiceRequestStatusController extends Controller
{
    public function update(UpdateServiceRequestStatusRequest $request, ServiceRequest $serviceRequest, UpdateServiceRequestStatus $updateServiceRequestStatus): ServiceRequestResource
    {
        return ServiceRequestResource::make($updateServiceRequestStatus->handle($serviceRequest, ServiceRequestStatus::from($request->validated('status')))->load(ServiceRequestResource::RELATIONS));
    }
}
