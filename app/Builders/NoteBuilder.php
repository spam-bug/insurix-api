<?php

declare(strict_types=1);

namespace App\Builders;

use App\Enums\NoteLink;
use App\Models\Note;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends Builder<Note>
 */
class NoteBuilder extends Builder
{
    /**
     * Notes about that kind of record. A note counts as its most specific link: a policy note is
     * a policy note, a promoted lead's note is a client note.
     */
    public function linkedTo(?NoteLink $link): static
    {
        return match ($link) {
            null => $this,
            NoteLink::Policy => $this->whereNotNull('policy_id'),
            NoteLink::Client => $this->whereNull('policy_id')->whereNotNull('client_id'),
            NoteLink::Lead => $this->whereNull('policy_id')->whereNull('client_id')->whereNotNull('lead_id'),
            NoteLink::None => $this->whereNull('policy_id')->whereNull('client_id')->whereNull('lead_id'),
        };
    }

    /** Matches the note's text or the name of the person it's about. */
    public function search(?string $search): static
    {
        if (blank($search)) {
            return $this;
        }

        return $this->where(fn (Builder $query) => $query
            ->whereLike('body', "%{$search}%")
            ->orWhereHas('lead', fn (Builder $lead) => $lead->whereLike('name', "%{$search}%"))
            ->orWhereHas('client', fn (Builder $client) => $client->whereLike('name', "%{$search}%")));
    }

    /** Pinned notes first (latest pinned on top), then the newest. */
    public function pinnedFirst(): static
    {
        return $this->orderByRaw('pinned_at is null')->latest('pinned_at')->latest()->latest('id');
    }
}
