<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Forms\DeleteFormResponse;
use App\Actions\Forms\ListFormResponses;
use App\DataTransferObject\Forms\FormResponseFiltersData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Forms\ListFormResponsesRequest;
use App\Http\Requests\Api\V1\Forms\ManageFormRequest;
use App\Http\Resources\Api\V1\FormResponseResource;
use App\Models\FormResponse;
use App\Models\Workspace;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class FormResponseController extends Controller
{
    /** For one form, also how many responses are in each status, for the tabs. */
    public function index(ListFormResponsesRequest $request, Workspace $workspace, ListFormResponses $listFormResponses): AnonymousResourceCollection
    {
        $filters = FormResponseFiltersData::fromArray($request->validated());

        return FormResponseResource::collection($listFormResponses->handle($workspace, $filters))
            ->additional(['meta' => ['counts' => $filters->formId ? (object) $listFormResponses->counts($workspace, $filters->formId) : null]]);
    }

    public function destroy(ManageFormRequest $request, FormResponse $formResponse, DeleteFormResponse $deleteFormResponse): Response
    {
        $deleteFormResponse->handle($formResponse);

        return response()->noContent();
    }
}
