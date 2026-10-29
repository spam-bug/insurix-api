<?php

declare(strict_types=1);

namespace App\Support;

use BackedEnum;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
use Throwable;

/**
 * Cleans one spreadsheet value into what Insurix stores. Each method returns null for an empty
 * cell and throws InvalidArgumentException, with a message for the agent, when it can't be read.
 */
class ImportValue
{
    /** Cells that people fill in to mean "nothing". */
    private const BLANKS = ['', '-', '--', 'n/a', 'na', 'none', 'null', 'nil'];

    /** Date layouts tried in order; month-first, as Philippine spreadsheets usually are. */
    private const DATE_FORMATS = ['Y-m-d', 'm/d/Y', 'n/j/Y', 'm/d/y', 'n/j/y', 'm-d-Y', 'Y/m/d', 'F j, Y', 'M j, Y', 'j F Y', 'j M Y', 'd-M-Y', 'd-M-y'];

    public static function text(string $value, int $max = 255): ?string
    {
        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');

        if (in_array(mb_strtolower($value), self::BLANKS, true)) {
            return null;
        }
        if (mb_strlen($value) > $max) {
            throw new InvalidArgumentException("Longer than {$max} characters.");
        }

        return $value;
    }

    public static function email(string $value): ?string
    {
        $email = self::text($value);

        if ($email === null) {
            return null;
        }
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException("“{$email}” isn't an email address.");
        }
        if (isset(self::EMAIL_DOMAIN_TYPOS[mb_strtolower(substr(strrchr($email, '@'), 1))])) {
            throw new InvalidArgumentException("“{$email}” looks like a typo in the domain.");
        }

        return mb_strtolower($email);
    }

    /** Philippine mobiles in any common form (0917…, +63 917…, 917…) come out as 0917 123 4567. */
    public static function phone(string $value): ?string
    {
        $phone = self::text($value, 50);

        if ($phone === null) {
            return null;
        }

        $digits = self::phoneKey($phone);

        if (preg_match('/\p{L}/u', $phone)) {
            throw new InvalidArgumentException("“{$phone}” has words in it besides the number.");
        }
        if (strlen($digits) < 7) {
            throw new InvalidArgumentException("“{$phone}” is too short for a phone number.");
        }
        if (preg_match('/^09\d{9}$/', $digits)) {
            return substr($digits, 0, 4).' '.substr($digits, 4, 3).' '.substr($digits, 7);
        }

        return $phone;
    }

    /** The digits that identify a number, so 0917…, +63917… and 917… count as the same phone. */
    public static function phoneKey(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';

        return match (true) {
            preg_match('/^639\d{9}$/', $digits) === 1 => '0'.substr($digits, 2),
            preg_match('/^9\d{9}$/', $digits) === 1 => '0'.$digits,
            default => $digits,
        };
    }

    /** Dates as typed, or as Excel stores them (a day count, such as 45231). */
    public static function date(string $value): ?string
    {
        $text = self::text($value);

        if ($text === null) {
            return null;
        }
        if (preg_match('/^\d{5}(\.\d+)?$/', $text)) {
            return CarbonImmutable::create(1899, 12, 30)->addDays((int) $text)->toDateString();
        }

        foreach (self::DATE_FORMATS as $format) {
            try {
                $date = CarbonImmutable::createFromFormat("!{$format}", $text);
            } catch (Throwable) {
                continue;
            }
            if ($date !== null && $date->format($format) === $text) {
                return $date->toDateString();
            }
        }

        throw new InvalidArgumentException("“{$text}” isn't a date we can read. Use a form like 2026-03-15 or 03/15/2026.");
    }

    /** Pesos with or without ₱, PHP, P or thousands separators. */
    public static function money(string $value): ?string
    {
        $text = self::text($value);

        if ($text === null) {
            return null;
        }

        $number = preg_replace('/^(₱|php|p)\s*|,|\s/iu', '', $text) ?? '';

        if (! is_numeric($number) || (float) $number <= 0) {
            throw new InvalidArgumentException("“{$text}” isn't an amount above zero.");
        }

        return number_format((float) $number, 2, '.', '');
    }

    public static function percent(string $value): ?string
    {
        $text = self::text($value);

        if ($text === null) {
            return null;
        }

        $number = rtrim($text, ' %');

        if (! is_numeric($number) || (float) $number < 0 || (float) $number > 100) {
            throw new InvalidArgumentException("“{$text}” isn't a percentage from 0 to 100.");
        }

        return number_format((float) $number, 2, '.', '');
    }

    /**
     * Matches a word to an enum case: its value, its label, or one of the extra words given.
     *
     * @template T of BackedEnum
     *
     * @param  class-string<T>  $enum
     * @param  array<string, T>  $words  other words agents use, lowercase
     * @return T|null
     */
    public static function choice(string $value, string $enum, array $words = []): ?BackedEnum
    {
        $text = self::text($value);

        if ($text === null) {
            return null;
        }

        $key = mb_strtolower($text);

        foreach ($enum::cases() as $case) {
            $label = method_exists($case, 'label') ? mb_strtolower($case->label()) : null;
            if ($key === $case->value || $key === str_replace('_', ' ', (string) $case->value) || $key === $label) {
                return $case;
            }
        }

        return $words[$key] ?? throw new InvalidArgumentException("“{$text}” isn't one of the options.");
    }

    /** Common slips in email domains. */
    private const EMAIL_DOMAIN_TYPOS = [
        'gmial.com' => 'gmail.com', 'gamil.com' => 'gmail.com', 'gmai.com' => 'gmail.com', 'gmail.co' => 'gmail.com', 'gmail.con' => 'gmail.com',
        'yaho.com' => 'yahoo.com', 'yahoo.con' => 'yahoo.com', 'hotmial.com' => 'hotmail.com', 'hotmail.con' => 'hotmail.com', 'outlok.com' => 'outlook.com',
    ];

    /**
     * A day-first date (31/12/1990), but only when it can't be month-first, so nothing is guessed
     * for something like 05/06/1990.
     */
    public static function suggestDate(string $value): ?string
    {
        if (! preg_match('/^\s*(\d{1,2})[\/.\-](\d{1,2})[\/.\-](\d{2}|\d{4})\s*$/', $value, $parts)) {
            return null;
        }

        [, $day, $month, $year] = array_map('intval', $parts);
        $year = $year < 100 ? ($year > (int) date('y') ? 1900 : 2000) + $year : $year;

        if ($day <= 12 || ! checkdate($month, $day, $year)) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    /** Spaces, a doubled @, trailing punctuation and common domain typos. */
    public static function suggestEmail(string $value): ?string
    {
        $email = rtrim(mb_strtolower(preg_replace(['/\s+/', '/@+/'], ['', '@'], $value) ?? ''), '.,;:');
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
        $email = $local.'@'.(self::EMAIL_DOMAIN_TYPOS[$domain] ?? $domain);

        return $email !== mb_strtolower(trim($value)) && filter_var($email, FILTER_VALIDATE_EMAIL) !== false ? $email : null;
    }

    /** The mobile number inside extra text, such as "0917 123 4567 (home)". */
    public static function suggestPhone(string $value): ?string
    {
        return preg_match('/(\+?63[\s-]?|0)?9\d{2}[\s-]?\d{3}[\s-]?\d{4}/', $value, $match) ? $match[0] : null;
    }

    /** The amount inside extra text, such as "₱1,500 per month". */
    public static function suggestMoney(string $value): ?string
    {
        return preg_match('/\d[\d,]*(\.\d+)?/', $value, $match) ? str_replace(',', '', $match[0]) : null;
    }

    /**
     * The option a misspelling most likely meant ("Monthy" → "monthly"): one or two letters off,
     * and clearly closer than any other option.
     *
     * @param  array<int, string>  $options
     */
    public static function closest(string $value, array $options): ?string
    {
        $value = mb_strtolower(trim($value));
        $distances = [];

        foreach (array_unique($options) as $option) {
            $distances[$option] = levenshtein($value, $option);
        }
        asort($distances);

        $best = array_key_first($distances);
        $distance = $distances[$best] ?? PHP_INT_MAX;
        $runnerUp = array_values($distances)[1] ?? PHP_INT_MAX;

        return $best !== null && $distance <= 2 && $distance < mb_strlen($best) / 3 && $runnerUp > $distance ? $best : null;
    }
}
