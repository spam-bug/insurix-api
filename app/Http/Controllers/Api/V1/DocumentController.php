<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Documents\DeleteDocument;
use App\Actions\Documents\ListDocuments;
use App\Actions\Documents\UpdateDocument;
use App\DataTransferObject\Documents\DocumentData;
use App\DataTransferObject\Documents\DocumentFiltersData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Documents\ListDocumentsRequest;
use App\Http\Requests\Api\V1\Documents\UpdateDocumentRequest;
use App\Http\Resources\Api\V1\DocumentResource;
use App\Models\Document;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class DocumentController extends Controller
{
    public function index(ListDocumentsRequest $request, #[CurrentUser] User $agent, ListDocuments $listDocuments): AnonymousResourceCollection
    {
        return DocumentResource::collection($listDocuments->handle($agent, DocumentFiltersData::fromArray($request->validated())));
    }

    public function show(Document $document): DocumentResource
    {
        return DocumentResource::make($document->load(DocumentResource::RELATIONS));
    }

    public function update(UpdateDocumentRequest $request, Document $document, UpdateDocument $updateDocument): DocumentResource
    {
        return DocumentResource::make($updateDocument->handle($document, DocumentData::fromArray($request->validated()))->load(DocumentResource::RELATIONS));
    }

    public function destroy(Document $document, DeleteDocument $deleteDocument): Response
    {
        $deleteDocument->handle($document);

        return response()->noContent();
    }
}
