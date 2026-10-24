<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Documents\DeleteDocument;
use App\Actions\Documents\ListDocuments;
use App\Actions\Documents\StoreDocument;
use App\Actions\Documents\UpdateDocument;
use App\DataTransferObject\Documents\DocumentData;
use App\DataTransferObject\Documents\DocumentFiltersData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Documents\ListDocumentsRequest;
use App\Http\Requests\Api\V1\Documents\StoreDocumentRequest;
use App\Http\Requests\Api\V1\Documents\UpdateDocumentRequest;
use App\Http\Resources\Api\V1\DocumentResource;
use App\Models\Document;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class DocumentController extends Controller
{
    public function index(ListDocumentsRequest $request, Workspace $workspace, ListDocuments $listDocuments): AnonymousResourceCollection
    {
        return DocumentResource::collection($listDocuments->handle($workspace, DocumentFiltersData::fromArray($request->validated())));
    }

    /** An upload from the Documents page: for a client, or unassigned when none is picked. */
    public function store(StoreDocumentRequest $request, Workspace $workspace, StoreDocument $storeDocument): JsonResponse
    {
        $document = $storeDocument->handle($workspace, $request->file('file'), DocumentData::fromArray($request->safe()->except('file')));

        return DocumentResource::make($document->load(DocumentResource::RELATIONS))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Document $document): DocumentResource
    {
        return DocumentResource::make($document->load(DocumentResource::RELATIONS));
    }

    public function update(UpdateDocumentRequest $request, Document $document, UpdateDocument $updateDocument): DocumentResource
    {
        $data = DocumentData::fromArray(['client_id' => $document->client_id, ...$request->validated()]);

        return DocumentResource::make($updateDocument->handle($document, $data)->load(DocumentResource::RELATIONS));
    }

    public function destroy(Document $document, DeleteDocument $deleteDocument): Response
    {
        $deleteDocument->handle($document);

        return response()->noContent();
    }
}
