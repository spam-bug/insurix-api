<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Clients\ListUpcomingBirthdays;
use App\Http\Controllers\Controller;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;

class ClientBirthdayController extends Controller
{
    public function index(Workspace $workspace, ListUpcomingBirthdays $listUpcomingBirthdays): JsonResponse
    {
        return response()->json(['data' => $listUpcomingBirthdays->handle($workspace)]);
    }
}
