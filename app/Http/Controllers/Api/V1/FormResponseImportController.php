<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Forms\ImportFormResponses;
use App\DataTransferObject\Forms\ImportResponsesData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Forms\ImportResponsesRequest;
use App\Http\Resources\Api\V1\ImportResource;
use App\Models\Form;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

/** Starts an import from chosen responses; the import page then takes it from matching columns. */
class FormResponseImportController extends Controller
{
    public function store(ImportResponsesRequest $request, Form $form, #[CurrentUser] User $user, ImportFormResponses $importFormResponses): JsonResponse
    {
        return ImportResource::make($importFormResponses->handle($form, $user, ImportResponsesData::fromArray($request->validated())))
            ->response()
            ->setStatusCode(201);
    }
}
