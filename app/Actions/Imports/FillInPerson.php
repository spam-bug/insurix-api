<?php

declare(strict_types=1);

namespace App\Actions\Imports;

use App\Models\Client;
use App\Models\Lead;

class FillInPerson
{
    /**
     * Fills in only what the existing record doesn't have yet; nothing the agent already typed is
     * overwritten. Returns the details it filled in.
     *
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    public function handle(Client|Lead $person, array $data): array
    {
        $filled = [];

        foreach ($data as $field => $value) {
            if ($value !== null && $person->isFillable($field) && blank($person->getAttribute($field))) {
                $person->setAttribute($field, $value);
                $filled[] = $field;
            }
        }
        $person->save();

        return $filled;
    }
}
