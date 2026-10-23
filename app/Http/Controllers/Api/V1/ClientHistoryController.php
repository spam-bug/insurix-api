<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Clients\ListClientHistory;
use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\JsonResponse;

class ClientHistoryController extends Controller
{
    /**
     * The client's notes, meetings, policy changes and documents as one timeline, newest first.
     */
    public function index(Client $client, ListClientHistory $listClientHistory): JsonResponse
    {
        return response()->json(['data' => $listClientHistory->handle($client)]);
    }
}
