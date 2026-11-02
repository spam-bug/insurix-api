<?php

declare(strict_types=1);

namespace App\Http\Controllers\Staff;

use App\Actions\Staff\ListUsersForStaff;
use App\DataTransferObject\Staff\StaffSearchFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\ListStaffUsersRequest;
use App\Http\Resources\Staff\StaffUserResource;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(ListStaffUsersRequest $request, ListUsersForStaff $listUsers): Response
    {
        return Inertia::render('staff/users/index', [
            'users' => StaffUserResource::collection($listUsers->handle(StaffSearchFilters::fromArray($request->validated()))),
            'filters' => $request->validated(),
        ]);
    }
}
