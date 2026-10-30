<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Forms\ShowPublicForm;
use App\Actions\Forms\SubmitFormResponse;
use App\DataTransferObject\Forms\FormResponseData;
use App\Enums\FormStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Forms\SubmitFormResponseRequest;
use App\Models\Form;
use Illuminate\Http\JsonResponse;

/** The public side of a form: no sign-in, identified only by the link's token. A form never published is not found. */
class PublicFormController extends Controller
{
    /** The published form, or only its status once closed, with who the visitor is writing to. */
    public function show(Form $form, ShowPublicForm $showPublicForm): JsonResponse
    {
        abort_if($form->published === null, 404);

        return response()->json(['data' => $showPublicForm->handle($form, $form->status === FormStatus::Closed ? null : $form->published)]);
    }

    public function store(SubmitFormResponseRequest $request, Form $form, SubmitFormResponse $submitFormResponse): JsonResponse
    {
        $submitFormResponse->handle($form, FormResponseData::fromArray($request->validated()));

        return response()->json(['data' => ['submitted' => true]], 201);
    }
}
