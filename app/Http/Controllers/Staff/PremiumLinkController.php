<?php

declare(strict_types=1);

namespace App\Http\Controllers\Staff;

use App\Actions\Staff\ListPremiumLinksForStaff;
use App\Actions\Staff\MakePremiumLink;
use App\DataTransferObject\Premium\PremiumLinkData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\ListStaffPremiumLinksRequest;
use App\Http\Requests\Staff\StorePremiumLinkRequest;
use App\Http\Resources\Staff\StaffPremiumLinkResource;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PremiumLinkController extends Controller
{
    public function index(ListStaffPremiumLinksRequest $request, ListPremiumLinksForStaff $listPremiumLinks): Response
    {
        return Inertia::render('staff/premium-links/index', [
            'links' => StaffPremiumLinkResource::collection($listPremiumLinks->handle((int) $request->validated('page', 1))),
            'can_change' => (bool) $request->user('staff')->can('change-accounts'),
        ]);
    }

    public function store(StorePremiumLinkRequest $request, MakePremiumLink $makePremiumLink): RedirectResponse
    {
        $makePremiumLink->handle(PremiumLinkData::fromArray($request->validated()), $request->user('staff'));

        return back()->with('status', 'Premium link made. Copy it from the list.');
    }
}
