<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Actions\Account\ConfirmTwoFactorSetup;
use App\Actions\Account\DisableTwoFactor;
use App\Actions\Account\RegenerateRecoveryCodes;
use App\Actions\Account\StartTwoFactorSetup;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Account\ConfirmPasswordRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Two-factor with an authenticator app: start (password), scan the QR code, confirm a code
 * to turn it on and get recovery codes. Turning it off and new recovery codes need the
 * password again.
 */
class TwoFactorController extends Controller
{
    public function store(ConfirmPasswordRequest $request, #[CurrentUser] User $agent, StartTwoFactorSetup $startTwoFactorSetup): JsonResponse
    {
        return response()->json(['data' => $startTwoFactorSetup->handle($agent)]);
    }

    public function confirm(Request $request, #[CurrentUser] User $agent, ConfirmTwoFactorSetup $confirmTwoFactorSetup): JsonResponse
    {
        $request->validate(['code' => ['required', 'string', 'max:10']]);

        $codes = $confirmTwoFactorSetup->handle($agent, preg_replace('/\s+/', '', $request->string('code')->toString()));

        return response()->json(['data' => ['recovery_codes' => $codes]]);
    }

    public function destroy(ConfirmPasswordRequest $request, #[CurrentUser] User $agent, DisableTwoFactor $disableTwoFactor): Response
    {
        $disableTwoFactor->handle($agent);

        return response()->noContent();
    }

    public function regenerateRecoveryCodes(ConfirmPasswordRequest $request, #[CurrentUser] User $agent, RegenerateRecoveryCodes $regenerateRecoveryCodes): JsonResponse
    {
        abort_unless($agent->hasEnabledTwoFactorAuthentication(), 422, 'Turn on two-factor authentication first.');

        return response()->json(['data' => ['recovery_codes' => $regenerateRecoveryCodes->handle($agent)]]);
    }
}
