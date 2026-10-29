<?php

declare(strict_types=1);

namespace App\Enums;

/** What a note is about, for filtering the notes list. */
enum NoteLink: string
{
    case Lead = 'lead';
    case Client = 'client';
    case Policy = 'policy';
    case None = 'none';
}
