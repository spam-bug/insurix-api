<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Imports\ListImportRowsRequest;
use App\Http\Resources\Api\V1\ImportRowResource;
use App\Models\Import;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ImportRowController extends Controller
{
    /** The checked rows, 50 a page, in sheet order; problems first when not filtered. */
    public function index(ListImportRowsRequest $request, Import $import): AnonymousResourceCollection
    {
        return ImportRowResource::collection($import->rows()
            ->when($request->validated('status'), fn (Builder $query, string $status) => $query->where('status', $status))
            ->orderBy('row_number')
            ->paginate(50));
    }
}
