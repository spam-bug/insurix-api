<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\LeadForms\SubmitLeadForm;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LeadForms\SubmitLeadFormRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/** The public side of a shareable form: no sign-in, identified only by the link's token. */
class PublicLeadFormController extends Controller
{
    public function show(string $token): JsonResponse
    {
        $agent = $this->agentFor($token);

        return response()->json(['data' => ['agent' => [
            'name' => $agent->name,
            'bio' => $agent->bio,
            'avatar_url' => $agent->avatarUrl(),
            'logo_url' => $agent->logoUrl(),
        ]]]);
    }

    public function submit(SubmitLeadFormRequest $request, string $token, SubmitLeadForm $submitLeadForm): JsonResponse
    {
        $agent = $this->agentFor($token);

        // Answer bots exactly like people, so the honeypot doesn't give itself away.
        if ($request->isLikelySpam()) {
            Log::info('Dropped a lead form submission that filled in the honeypot.', ['agent_id' => $agent->id]);
        } else {
            $submitLeadForm->handle($agent, $request->toData());
        }

        return response()->json(['data' => ['submitted' => true]], 201);
    }

    private function agentFor(string $token): User
    {
        return User::where('lead_form_token', $token)->firstOrFail();
    }
}
