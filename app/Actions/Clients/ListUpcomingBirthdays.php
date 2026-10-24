<?php

declare(strict_types=1);

namespace App\Actions\Clients;

use App\Models\Client;
use App\Models\Workspace;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ListUpcomingBirthdays
{
    /** Birthdays this many days out show up. */
    public const WINDOW_DAYS = 30;

    /**
     * Clients whose birthday falls today or in the coming days, soonest first, with the age they
     * turn, so the agent can send a greeting. Computed in PHP, since birthdays wrap around the year.
     * Someone born on Feb 29 celebrates on Feb 28 in other years.
     *
     * @return Collection<int, array{client: array{id: string, name: string, phone: string|null}, birthday: string, turns: int}>
     */
    public function handle(Workspace $workspace): Collection
    {
        $today = CarbonImmutable::now($workspace->timezone)->startOfDay();

        return Cache::tags("workspace:{$workspace->id}")->remember("clients:{$workspace->id}:birthdays:{$today->toDateString()}", now()->addHour(), function () use ($workspace, $today) {
            return $workspace->clients()
                ->whereNotNull('birthdate')
                ->get(['id', 'name', 'phone', 'birthdate'])
                ->map(function (Client $client) use ($today) {
                    $birthday = $this->birthdayIn($client->birthdate, $today->year);
                    if ($birthday < $today->toDateString()) {
                        $birthday = $this->birthdayIn($client->birthdate, $today->year + 1);
                    }

                    return [
                        'client' => ['id' => $client->id, 'name' => $client->name, 'phone' => $client->phone],
                        'birthday' => $birthday,
                        'turns' => (int) substr($birthday, 0, 4) - $client->birthdate->year,
                    ];
                })
                ->filter(fn (array $entry) => $entry['birthday'] <= $today->addDays(self::WINDOW_DAYS)->toDateString())
                ->sortBy('birthday')
                ->values();
        });
    }

    /** The birthday in $year as YYYY-MM-DD. Dates are compared as strings, so timezones don't shift them. */
    private function birthdayIn(CarbonImmutable $birthdate, int $year): string
    {
        $isLeapDay = $birthdate->month === 2 && $birthdate->day === 29;

        return $isLeapDay && ! CarbonImmutable::create($year)->isLeapYear()
            ? sprintf('%d-02-28', $year)
            : sprintf('%d-%02d-%02d', $year, $birthdate->month, $birthdate->day);
    }
}
