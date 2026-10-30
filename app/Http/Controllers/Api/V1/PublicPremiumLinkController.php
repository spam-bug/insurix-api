<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PremiumLinkResource;
use App\Models\PremiumLink;

/** A premium link opened by anyone, signed in or not, before they claim it. */
class PublicPremiumLinkController extends Controller
{
    public function show(PremiumLink $premiumLink): PremiumLinkResource
    {
        return PremiumLinkResource::make($premiumLink->loadCount('grants'));
    }
}
