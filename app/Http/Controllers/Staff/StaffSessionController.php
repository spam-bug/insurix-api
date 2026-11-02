<?php

declare(strict_types=1);

namespace App\Http\Controllers\Staff;

use App\Actions\Staff\EndStaffSession;
use App\Actions\Staff\StartStaffLogin;
use App\DataTransferObject\Staff\StaffLoginData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\StaffLoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StaffSessionController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('staff/login');
    }

    public function store(StaffLoginRequest $request, StartStaffLogin $startStaffLogin): RedirectResponse
    {
        $staff = $startStaffLogin->handle(StaffLoginData::fromArray($request->validated()), $request->session());

        return $staff->hasEnabledTwoFactorAuthentication()
            ? redirect()->route('staff.two-factor-challenge.create')
            : redirect()->route('staff.two-factor.create');
    }

    public function destroy(Request $request, EndStaffSession $endStaffSession): RedirectResponse
    {
        $endStaffSession->handle($request->session());

        return redirect()->route('staff.login');
    }
}
