<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Account;

use App\Actions\Account\ConfirmTwoFactorSetup;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Account\ConfirmTwoFactorRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

class TwoFactorConfirmationController extends Controller
{
    public function store(ConfirmTwoFactorRequest $request, #[CurrentUser] User $agent, ConfirmTwoFactorSetup $confirmTwoFactorSetup): JsonResponse
    {
        return response()->json(['data' => ['recovery_codes' => $confirmTwoFactorSetup->handle($agent, $request->validated('code'))]]);
    }
}
