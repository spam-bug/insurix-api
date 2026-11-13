<?php

declare(strict_types=1);

namespace App\Builders;

use App\Enums\LeadActivityType;
use App\Models\Client;
use App\Models\FollowUp;
use App\Models\LeadActivity;
use App\Models\Note;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * @extends Builder<Client>
 */
class ClientBuilder extends Builder
{
    /** What `last_contact_at` holds for a client nobody has been in touch with yet. */
    public const string NEVER_CONTACTED = '1970-01-01 00:00:00';

    /** Adds `last_contact_at`: the latest completed follow-up, note, or touch on the lead they came from. */
    public function withLastContact(): static
    {
        return $this->addSelect(['last_contact_at' => DB::query()->selectRaw($this->lastContact()->getValue($this->getGrammar()))]);
    }

    /** Clients nobody has been in touch with first, then the longest quiet. */
    public function orderByLastContact(): static
    {
        return $this->orderBy($this->lastContact());
    }

    /**
     * The newest of three dates. SQLite's max() and the others' greatest() both give null when
     * one side is null, so each side falls back to NEVER_CONTACTED.
     */
    private function lastContact(): Expression
    {
        $newest = $this->getConnection()->getDriverName() === 'sqlite' ? 'max' : 'greatest';

        $sides = collect([
            FollowUp::query()->selectRaw('max(completed_at)')->whereColumn('client_id', 'clients.id'),
            Note::query()->selectRaw('max(created_at)')->whereColumn('client_id', 'clients.id'),
            LeadActivity::query()->selectRaw('max(created_at)')->whereColumn('lead_id', 'clients.lead_id')->whereIn('type', LeadActivityType::touches()),
        ])->map(fn (Builder $query) => "coalesce(({$query->toRawSql()}), '".self::NEVER_CONTACTED."')");

        return DB::raw("{$newest}({$sides->implode(', ')})");
    }
}
