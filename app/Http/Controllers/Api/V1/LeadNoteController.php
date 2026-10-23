<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Leads\AddLeadNote;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Leads\StoreLeadNoteRequest;
use App\Http\Resources\Api\V1\LeadActivityResource;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

class LeadNoteController extends Controller
{
    public function store(StoreLeadNoteRequest $request, Lead $lead, #[CurrentUser] User $agent, AddLeadNote $addLeadNote): JsonResponse
    {
        return LeadActivityResource::make($addLeadNote->handle($lead, $request->validated('body'), $agent)->load('user:id,name'))
            ->response()
            ->setStatusCode(201);
    }
}
