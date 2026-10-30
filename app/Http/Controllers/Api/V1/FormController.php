<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Forms\CreateForm;
use App\Actions\Forms\DeleteForm;
use App\Actions\Forms\ListForms;
use App\Actions\Forms\UpdateForm;
use App\DataTransferObject\Forms\FormData;
use App\DataTransferObject\Forms\UpdateFormData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Forms\ManageFormRequest;
use App\Http\Requests\Api\V1\Forms\StoreFormRequest;
use App\Http\Requests\Api\V1\Forms\UpdateFormRequest;
use App\Http\Resources\Api\V1\FormResource;
use App\Models\Form;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class FormController extends Controller
{
    /** With the plan's form limit, so the page can say how many are left. */
    public function index(Workspace $workspace, ListForms $listForms): AnonymousResourceCollection
    {
        return FormResource::collection($listForms->handle($workspace))
            ->additional(['meta' => ['limit' => $workspace->currentPlan()->formLimit()]]);
    }

    public function store(StoreFormRequest $request, Workspace $workspace, CreateForm $createForm): JsonResponse
    {
        return FormResource::make($createForm->handle($workspace, FormData::fromArray($request->validated())))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Form $form): FormResource
    {
        return FormResource::make($form);
    }

    public function update(UpdateFormRequest $request, Form $form, UpdateForm $updateForm): FormResource
    {
        return FormResource::make($updateForm->handle($form, UpdateFormData::fromArray($request->validated())));
    }

    public function destroy(ManageFormRequest $request, Form $form, DeleteForm $deleteForm): Response
    {
        $deleteForm->handle($form);

        return response()->noContent();
    }
}
