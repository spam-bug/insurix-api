<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Documents\DeleteDocument;
use App\Actions\Documents\ListDocuments;
use App\Actions\Documents\StoreDocument;
use App\Actions\Documents\UpdateDocument;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Documents\ListDocumentsRequest;
use App\Http\Requests\Api\V1\Documents\StoreDocumentRequest;
use App\Http\Requests\Api\V1\Documents\UpdateDocumentRequest;
use App\Http\Resources\Api\V1\DocumentResource;
use App\Models\Client;
use App\Models\Document;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Files the agent keeps for clients. Uploads go to a client; files are only served here. */
class DocumentController extends Controller
{
    public function index(ListDocumentsRequest $request, #[CurrentUser] User $agent, ListDocuments $listDocuments): AnonymousResourceCollection
    {
        return DocumentResource::collection($listDocuments->handle($agent, $request->toData()));
    }

    public function store(StoreDocumentRequest $request, Client $client, StoreDocument $storeDocument): JsonResponse
    {
        Gate::authorize('update', $client);

        $document = $storeDocument->handle($client, $request->file('file'), $request->toData());

        return DocumentResource::make($document->load(['client:id,name', 'policy:id,product_name,policy_number']))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Document $document): DocumentResource
    {
        Gate::authorize('view', $document);

        return DocumentResource::make($document->load(['client:id,name', 'policy:id,product_name,policy_number']));
    }

    public function update(UpdateDocumentRequest $request, Document $document, UpdateDocument $updateDocument): DocumentResource
    {
        Gate::authorize('update', $document);

        return DocumentResource::make($updateDocument->handle($document, $request->toData())->load(['client:id,name', 'policy:id,product_name,policy_number']));
    }

    public function destroy(Document $document, DeleteDocument $deleteDocument): Response
    {
        Gate::authorize('delete', $document);

        $deleteDocument->handle($document);

        return response()->noContent();
    }

    /**
     * The file itself, as an attachment saved under the document's name.
     */
    public function download(Document $document): StreamedResponse
    {
        Gate::authorize('view', $document);

        $disk = Storage::disk(Document::DISK);
        abort_unless($disk->exists($document->path), 404, 'This file is missing. Upload it again.');

        return $disk->download($document->path, $document->downloadName(), ['Content-Type' => $document->mime_type]);
    }
}
