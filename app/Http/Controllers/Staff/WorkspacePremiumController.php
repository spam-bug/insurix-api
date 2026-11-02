<?php

declare(strict_types=1);

namespace App\Http\Controllers\Staff;

use App\Actions\Staff\EndPremium;
use App\Actions\Staff\GivePremium;
use App\DataTransferObject\Premium\PremiumGrantData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\EndPremiumRequest;
use App\Http\Requests\Staff\GivePremiumRequest;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;

class WorkspacePremiumController extends Controller
{
    public function store(GivePremiumRequest $request, Workspace $workspace, GivePremium $givePremium): RedirectResponse
    {
        $givePremium->handle($workspace, PremiumGrantData::fromArray($request->validated()), $request->user('staff'));

        return back()->with('status', "Premium given to {$workspace->name}");
    }

    public function destroy(EndPremiumRequest $request, Workspace $workspace, EndPremium $endPremium): RedirectResponse
    {
        $endPremium->handle($workspace, $request->user('staff'));

        return back()->with('status', "{$workspace->name} is back on Free");
    }
}
