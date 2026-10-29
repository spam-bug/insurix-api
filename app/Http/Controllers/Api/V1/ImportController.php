<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Imports\CancelImport;
use App\Actions\Imports\CheckImport;
use App\Actions\Imports\StartImport;
use App\Enums\ImportKind;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Imports\MapImportRequest;
use App\Http\Requests\Api\V1\Imports\StartImportRequest;
use App\Http\Resources\Api\V1\ImportResource;
use App\Models\Import;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ImportController extends Controller
{
    public function store(StartImportRequest $request, Workspace $workspace, #[CurrentUser] User $user, StartImport $startImport): JsonResponse
    {
        return ImportResource::make($startImport->handle($workspace, $user, ImportKind::from($request->validated('kind')), $request->file('file')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Import $import): ImportResource
    {
        return ImportResource::make($import);
    }

    public function update(MapImportRequest $request, Import $import, CheckImport $checkImport): ImportResource
    {
        return ImportResource::make($checkImport->handle($import, $request->validated('mapping'))->refresh());
    }

    public function destroy(Import $import, CancelImport $cancelImport): Response
    {
        $cancelImport->handle($import);

        return response()->noContent();
    }
}
