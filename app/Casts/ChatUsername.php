<?php

declare(strict_types=1);

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Keeps only the username from whatever the agent pastes: "@maria.santos", "m.me/maria.santos",
 * "https://facebook.com/maria.santos" or "t.me/maria_s". A Facebook "profile.php?id=…" link keeps its id.
 *
 * @implements CastsAttributes<string|null, string|null>
 */
class ChatUsername implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (preg_match('/[?&]id=(\d+)/', $value, $match)) {
            return $match[1];
        }

        $path = parse_url(str_contains($value, '/') && ! str_contains($value, '://') ? "https://{$value}" : $value, PHP_URL_PATH) ?? $value;
        $segments = array_values(array_filter(explode('/', $path)));

        return ltrim(end($segments) ?: $value, '@');
    }
}
