<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Leads\UpdateLeadStatus;
use App\Enums\LeadStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Leads\UpdateLeadStatusRequest;
use App\Http\Resources\Api\V1\LeadResource;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;

class LeadStatusController extends Controller
{
    public function update(UpdateLeadStatusRequest $request, Lead $lead, #[CurrentUser] User $agent, UpdateLeadStatus $updateLeadStatus): LeadResource
    {
        return LeadResource::make($updateLeadStatus->handle($lead, LeadStatus::from($request->validated('status')), $agent));
    }
}
