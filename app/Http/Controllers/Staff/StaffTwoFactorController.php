<?php

declare(strict_types=1);

namespace App\Http\Controllers\Staff;

use App\Actions\Staff\ConfirmStaffTwoFactor;
use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\ConfirmStaffTwoFactorRequest;
use App\Models\Staff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Setting up two-factor, which every staff member does before using the console. */
class StaffTwoFactorController extends Controller
{
    public function create(Request $request, ConfirmStaffTwoFactor $confirmStaffTwoFactor): Response
    {
        /** @var Staff $staff */
        $staff = $request->user('staff');
        $confirmed = $staff->hasEnabledTwoFactorAuthentication();

        if (! $confirmed) {
            $confirmStaffTwoFactor->prepare($staff);
        }

        return Inertia::render('staff/two-factor-setup', [
            'confirmed' => $confirmed,
            'qr_code_svg' => $confirmed ? null : $staff->twoFactorQrCodeSvg(),
            'secret' => $confirmed ? null : decrypt($staff->two_factor_secret),
            'recovery_codes' => $confirmed ? $staff->recoveryCodes() : [],
        ]);
    }

    public function store(ConfirmStaffTwoFactorRequest $request, ConfirmStaffTwoFactor $confirmStaffTwoFactor): RedirectResponse
    {
        $confirmStaffTwoFactor->handle($request->user('staff'), $request->validated('code'));

        return redirect()->route('staff.two-factor.create');
    }
}
