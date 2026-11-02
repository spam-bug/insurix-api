<?php

declare(strict_types=1);

namespace App\Http\Controllers\Staff;

use App\Actions\Staff\PassStaffTwoFactorChallenge;
use App\Actions\Staff\StartStaffLogin;
use App\DataTransferObject\Staff\StaffTwoFactorCodeData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\StaffTwoFactorChallengeRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StaffTwoFactorChallengeController extends Controller
{
    public function create(Request $request): Response|RedirectResponse
    {
        if (! $request->session()->has(StartStaffLogin::PENDING_KEY)) {
            return redirect()->route('staff.login');
        }

        return Inertia::render('staff/two-factor-challenge');
    }

    public function store(StaffTwoFactorChallengeRequest $request, PassStaffTwoFactorChallenge $passChallenge): RedirectResponse
    {
        $passChallenge->handle(StaffTwoFactorCodeData::fromArray($request->validated()), $request->session());

        return redirect()->intended(route('staff.workspaces.index'));
    }
}
