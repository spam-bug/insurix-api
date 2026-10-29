<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Mentions\SuggestMentions;
use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

class MentionSuggestionController extends Controller
{
    public function index(Lead $lead, #[CurrentUser] User $user, SuggestMentions $suggestMentions): JsonResponse
    {
        return response()->json(['data' => $suggestMentions->handle($lead, $user)]);
    }
}
