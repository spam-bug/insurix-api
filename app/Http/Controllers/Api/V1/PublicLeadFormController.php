<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\LeadForms\SubmitLeadForm;
use App\DataTransferObject\Leads\LeadFormSubmissionData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LeadForms\SubmitLeadFormRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;

/** The public side of a shareable form: no sign-in, identified only by the link's token. */
class PublicLeadFormController extends Controller
{
    public function show(User $agent): JsonResponse
    {
        return response()->json(['data' => ['agent' => [
            'name' => $agent->name,
            'bio' => $agent->bio,
            'avatar_url' => $agent->avatarUrl(),
            'logo_url' => $agent->logoUrl(),
        ]]]);
    }

    public function store(SubmitLeadFormRequest $request, User $agent, SubmitLeadForm $submitLeadForm): JsonResponse
    {
        $submitLeadForm->handle($agent, LeadFormSubmissionData::fromArray($request->validated()));

        return response()->json(['data' => ['submitted' => true]], 201);
    }
}
