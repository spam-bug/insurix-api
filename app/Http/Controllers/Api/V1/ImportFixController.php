<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Imports\ApplyImportFixes;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ImportResource;
use App\Models\Import;

class ImportFixController extends Controller
{
    public function store(Import $import, ApplyImportFixes $applyImportFixes): ImportResource
    {
        return ImportResource::make($applyImportFixes->handle($import)->refresh());
    }
}
