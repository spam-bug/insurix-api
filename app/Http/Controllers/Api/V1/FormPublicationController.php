<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Forms\CloseForm;
use App\Actions\Forms\PublishForm;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Forms\ManageFormRequest;
use App\Http\Resources\Api\V1\FormResource;
use App\Models\Form;

/** Publishing puts the draft live; closing stops responses. */
class FormPublicationController extends Controller
{
    public function store(ManageFormRequest $request, Form $form, PublishForm $publishForm): FormResource
    {
        return FormResource::make($publishForm->handle($form));
    }

    public function destroy(ManageFormRequest $request, Form $form, CloseForm $closeForm): FormResource
    {
        return FormResource::make($closeForm->handle($form));
    }
}
