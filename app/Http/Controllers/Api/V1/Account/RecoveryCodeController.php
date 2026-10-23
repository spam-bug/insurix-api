<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Account;

use App\Actions\Account\RegenerateRecoveryCodes;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Account\ConfirmPasswordRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

class RecoveryCodeController extends Controller
{
    public function store(ConfirmPasswordRequest $request, #[CurrentUser] User $agent, RegenerateRecoveryCodes $regenerateRecoveryCodes): JsonResponse
    {
        return response()->json(['data' => ['recovery_codes' => $regenerateRecoveryCodes->handle($agent)]]);
    }
}
