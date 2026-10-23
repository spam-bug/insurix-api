<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Applications\AddRequirement;
use App\Actions\Applications\ApproveApplication;
use App\Actions\Applications\DeclineApplication;
use App\Actions\Applications\MoveApplicationStage;
use App\Actions\Applications\UpdateRequirement;
use App\Enums\PolicyStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Applications\ApproveApplicationRequest;
use App\Http\Requests\Api\V1\Applications\MoveApplicationStageRequest;
use App\Http\Requests\Api\V1\Applications\UpdateRequirementRequest;
use App\Http\Resources\Api\V1\PolicyResource;
use App\Models\ApplicationRequirement;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/**
 * A pending policy's application, tracked by hand from what the insurer says: its stage, the
 * requirements still owed, and the decision. Each change answers with the full policy.
 */
class ApplicationController extends Controller
{
    public function stage(MoveApplicationStageRequest $request, Policy $policy, #[CurrentUser] User $agent, MoveApplicationStage $moveApplicationStage): PolicyResource
    {
        $this->authorizePending($policy);

        return $this->policy($moveApplicationStage->handle($policy, $request->stage(), $agent));
    }

    public function approve(ApproveApplicationRequest $request, Policy $policy, #[CurrentUser] User $agent, ApproveApplication $approveApplication): PolicyResource
    {
        $this->authorizePending($policy);

        return $this->policy($approveApplication->handle(
            $policy,
            $request->filled('effective_date') ? $request->date('effective_date')?->toImmutable() : null,
            $request->filled('policy_number') ? $request->string('policy_number')->trim()->toString() : null,
            $agent,
        ));
    }

    public function decline(Request $request, Policy $policy, #[CurrentUser] User $agent, DeclineApplication $declineApplication): PolicyResource
    {
        $this->authorizePending($policy);
        $request->validate(['reason' => ['nullable', 'string', 'max:1000']]);

        return $this->policy($declineApplication->handle($policy, $request->filled('reason') ? $request->string('reason')->trim()->toString() : null, $agent));
    }

    public function storeRequirement(Request $request, Policy $policy, #[CurrentUser] User $agent, AddRequirement $addRequirement): PolicyResource
    {
        $this->authorizePending($policy);
        $request->validate(['title' => ['required', 'string', 'max:255']]);

        $addRequirement->handle($policy, $request->string('title')->trim()->toString(), $agent);

        return $this->policy($policy);
    }

    public function updateRequirement(UpdateRequirementRequest $request, ApplicationRequirement $requirement, #[CurrentUser] User $agent, UpdateRequirement $updateRequirement): PolicyResource
    {
        Gate::authorize('update', $requirement->policy);

        $updateRequirement->handle($requirement, $request->boolean('received'), $request->document(), $agent);

        return $this->policy($requirement->policy);
    }

    public function destroyRequirement(ApplicationRequirement $requirement): Response
    {
        Gate::authorize('update', $requirement->policy);

        $requirement->delete();

        return response()->noContent();
    }

    /**
     * Only the agent's own pending applications can move through these steps.
     */
    private function authorizePending(Policy $policy): void
    {
        Gate::authorize('update', $policy);

        abort_unless($policy->status === PolicyStatus::Pending, 422, 'Only a pending application can change this way.');
    }

    private function policy(Policy $policy): PolicyResource
    {
        return PolicyResource::make($policy->fresh()->load(PolicyResource::DETAIL_RELATIONS));
    }
}
