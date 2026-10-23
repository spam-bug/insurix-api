<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Leads\ListLeadActivities;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\LeadActivityResource;
use App\Models\Lead;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LeadActivityController extends Controller
{
    public function index(Lead $lead, ListLeadActivities $listLeadActivities): AnonymousResourceCollection
    {
        return LeadActivityResource::collection($listLeadActivities->handle($lead));
    }
}
