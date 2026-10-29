<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Notes\CreateNote;
use App\Actions\Notes\DeleteNote;
use App\Actions\Notes\ListNotes;
use App\Actions\Notes\UpdateNote;
use App\DataTransferObject\Notes\NoteData;
use App\DataTransferObject\Notes\NoteFiltersData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Notes\ListNotesRequest;
use App\Http\Requests\Api\V1\Notes\StoreNoteRequest;
use App\Http\Requests\Api\V1\Notes\UpdateNoteRequest;
use App\Http\Resources\Api\V1\NoteResource;
use App\Models\Note;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class NoteController extends Controller
{
    public function index(ListNotesRequest $request, Workspace $workspace, #[CurrentUser] User $user, ListNotes $listNotes): AnonymousResourceCollection
    {
        return NoteResource::collection($listNotes->handle($workspace, NoteFiltersData::fromArray($request->validated()), $user));
    }

    public function store(StoreNoteRequest $request, Workspace $workspace, #[CurrentUser] User $user, CreateNote $createNote): JsonResponse
    {
        return NoteResource::make($createNote->handle($workspace, NoteData::fromArray($request->validated()), $user)->load(NoteResource::RELATIONS))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Note $note): NoteResource
    {
        return NoteResource::make($note->load(NoteResource::RELATIONS));
    }

    public function update(UpdateNoteRequest $request, Note $note, UpdateNote $updateNote): NoteResource
    {
        return NoteResource::make($updateNote->handle($note, $request->validated('body'))->load(NoteResource::RELATIONS));
    }

    public function destroy(Note $note, DeleteNote $deleteNote): Response
    {
        $deleteNote->handle($note);

        return response()->noContent();
    }
}
