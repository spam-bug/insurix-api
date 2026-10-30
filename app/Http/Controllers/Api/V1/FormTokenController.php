<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Forms\RegenerateFormToken;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Forms\ManageFormRequest;
use App\Http\Resources\Api\V1\FormResource;
use App\Models\Form;

class FormTokenController extends Controller
{
    public function store(ManageFormRequest $request, Form $form, RegenerateFormToken $regenerateFormToken): FormResource
    {
        return FormResource::make($regenerateFormToken->handle($form));
    }
}
