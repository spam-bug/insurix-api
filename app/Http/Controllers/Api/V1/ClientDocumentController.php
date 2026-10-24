<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Documents\StoreDocument;
use App\DataTransferObject\Documents\DocumentData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Documents\StoreDocumentRequest;
use App\Http\Resources\Api\V1\DocumentResource;
use App\Models\Client;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;

class ClientDocumentController extends Controller
{
    public function store(StoreDocumentRequest $request, Workspace $workspace, Client $client, StoreDocument $storeDocument): JsonResponse
    {
        $document = $storeDocument->handle($workspace, $request->file('file'), DocumentData::fromArray([...$request->safe()->except('file'), 'client_id' => $client->id]));

        return DocumentResource::make($document->load(DocumentResource::RELATIONS))
            ->response()
            ->setStatusCode(201);
    }
}
