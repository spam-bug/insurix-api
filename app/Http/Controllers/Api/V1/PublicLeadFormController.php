<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\LeadForms\SubmitLeadForm;
use App\DataTransferObject\Leads\LeadFormSubmissionData;
use App\Enums\WorkspaceRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LeadForms\SubmitLeadFormRequest;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;

/** The public side of a shareable form: no sign-in, identified only by the link's token. */
class PublicLeadFormController extends Controller
{
    /** Who the visitor is writing to: the workspace's name, bio and logo, with the owner's photo. */
    public function show(Workspace $leadForm): JsonResponse
    {
        $owner = $leadForm->users()->wherePivot('role', WorkspaceRole::Owner)->first();

        return response()->json(['data' => ['agent' => [
            'name' => $leadForm->name,
            'bio' => $leadForm->bio,
            'avatar_url' => $owner?->avatarUrl(),
            'logo_url' => $leadForm->logoUrl(),
        ]]]);
    }

    public function store(SubmitLeadFormRequest $request, Workspace $leadForm, SubmitLeadForm $submitLeadForm): JsonResponse
    {
        $submitLeadForm->handle($leadForm, LeadFormSubmissionData::fromArray($request->validated()));

        return response()->json(['data' => ['submitted' => true]], 201);
    }
}
