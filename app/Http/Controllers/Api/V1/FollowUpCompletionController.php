<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\FollowUps\CompleteFollowUp;
use App\Actions\FollowUps\ReopenFollowUp;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\FollowUpResource;
use App\Models\FollowUp;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;

/** Ticking a follow-up off, and un-ticking it. */
class FollowUpCompletionController extends Controller
{
    public function store(FollowUp $followUp, #[CurrentUser] User $user, CompleteFollowUp $completeFollowUp): FollowUpResource
    {
        return FollowUpResource::make($completeFollowUp->handle($followUp, $user)->load(FollowUpResource::RELATIONS));
    }

    public function destroy(FollowUp $followUp, ReopenFollowUp $reopenFollowUp): FollowUpResource
    {
        return FollowUpResource::make($reopenFollowUp->handle($followUp)->load(FollowUpResource::RELATIONS));
    }
}
