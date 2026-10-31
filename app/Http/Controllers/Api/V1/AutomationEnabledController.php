<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Automations\SetAutomationEnabled;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Automations\UpdateAutomationEnabledRequest;
use App\Http\Resources\Api\V1\AutomationResource;
use App\Models\Automation;

class AutomationEnabledController extends Controller
{
    public function update(UpdateAutomationEnabledRequest $request, Automation $automation, SetAutomationEnabled $setAutomationEnabled): AutomationResource
    {
        return AutomationResource::make($setAutomationEnabled->handle($automation, $request->boolean('enabled')));
    }
}
