<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Imports\RunImport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Imports\RunImportRequest;
use App\Http\Resources\Api\V1\ImportResource;
use App\Models\Import;

class ImportRunController extends Controller
{
    public function store(RunImportRequest $request, Import $import, RunImport $runImport): ImportResource
    {
        return ImportResource::make($runImport->handle($import, $request->boolean('update_duplicates'))->refresh());
    }
}
