<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Notes\PinNote;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\NoteResource;
use App\Models\Note;

class NotePinController extends Controller
{
    public function store(Note $note, PinNote $pinNote): NoteResource
    {
        return NoteResource::make($pinNote->handle($note, true)->load(NoteResource::RELATIONS));
    }

    public function destroy(Note $note, PinNote $pinNote): NoteResource
    {
        return NoteResource::make($pinNote->handle($note, false)->load(NoteResource::RELATIONS));
    }
}
