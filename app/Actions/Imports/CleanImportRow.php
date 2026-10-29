<?php

declare(strict_types=1);

namespace App\Actions\Imports;

use App\Enums\CivilStatus;
use App\Enums\ImportField;
use App\Enums\ImportKind;
use App\Enums\LeadInterest;
use App\Enums\PaymentFrequency;
use App\Enums\PolicyStatus;
use App\Enums\PolicyType;
use App\Support\ImportValue;
use BackedEnum;
use InvalidArgumentException;

class CleanImportRow
{
    /**
     * Reads one row through the agent's column choices, with any fixes they accepted applied.
     * Values that can't be read become errors on their field, with a suggested fix when one is
     * safe to offer (and itself reads cleanly); the rest are cleaned. A client row with any
     * policy column filled in also describes a policy, which then needs a product, a premium and
     * a payment frequency.
     *
     * @param  array<string, int>  $mapping  field => column position
     * @param  array<int, string>  $row
     * @param  array<string, string>  $fixes  field => accepted value
     * @return array{values: array<string, string>, data: array<string, mixed>, errors: array<string, string>, suggestions: array<string, string>}
     */
    public function handle(ImportKind $kind, array $mapping, array $row, array $fixes = []): array
    {
        $values = [];
        $data = [];
        $errors = [];
        $suggestions = [];

        foreach ($mapping as $field => $column) {
            $values[$field] = $fixes[$field] ?? $row[$column] ?? '';
        }

        foreach ($values as $field => $value) {
            try {
                $data[$field] = $this->clean(ImportField::from($field), $value);
            } catch (InvalidArgumentException $exception) {
                $errors[$field] = $exception->getMessage();
                $suggestion = $this->suggest(ImportField::from($field), $value);
                if ($suggestion !== null) {
                    $suggestions[$field] = $suggestion;
                }
            }
        }

        $data['name'] ??= trim(($data['first_name'] ?? '').' '.($data['last_name'] ?? '')) ?: null;
        unset($data['first_name'], $data['last_name']);

        if ($data['name'] === null && ! isset($errors['name'])) {
            $errors['name'] = 'A name is needed.';
        }

        if ($kind === ImportKind::Clients) {
            [$data, $errors] = $this->splitPolicy($data, $errors);
        }

        return [
            'values' => $values,
            'data' => array_filter($data, fn (mixed $value) => $value !== null),
            'errors' => $errors,
            'suggestions' => $suggestions,
        ];
    }

    private function clean(ImportField $field, string $value): mixed
    {
        $choice = $this->choices($field);

        return match (true) {
            $choice !== null => ImportValue::choice($value, $choice['enum'], $choice['words'])?->value,
            $field === ImportField::Email => ImportValue::email($value),
            $field === ImportField::Phone => ImportValue::phone($value),
            in_array($field, [ImportField::Birthdate, ImportField::EffectiveDate, ImportField::RenewalDate], true) => ImportValue::date($value),
            in_array($field, [ImportField::Premium, ImportField::CoverageAmount], true) => ImportValue::money($value),
            $field === ImportField::CommissionRate => ImportValue::percent($value),
            $field === ImportField::Notes => ImportValue::text($value, 2000),
            default => ImportValue::text($value),
        };
    }

    /** A likely correction for a value that couldn't be read, offered only if it reads cleanly. */
    private function suggest(ImportField $field, string $value): ?string
    {
        $choice = $this->choices($field);

        $suggestion = match (true) {
            $choice !== null => ImportValue::closest($value, [
                ...array_map(fn (BackedEnum $case) => str_replace('_', ' ', (string) $case->value), $choice['enum']::cases()),
                ...array_keys($choice['words']),
            ]),
            $field === ImportField::Email => ImportValue::suggestEmail($value),
            $field === ImportField::Phone => ImportValue::suggestPhone($value),
            in_array($field, [ImportField::Birthdate, ImportField::EffectiveDate, ImportField::RenewalDate], true) => ImportValue::suggestDate($value),
            in_array($field, [ImportField::Premium, ImportField::CoverageAmount], true) => ImportValue::suggestMoney($value),
            default => null,
        };

        if ($suggestion === null) {
            return null;
        }

        try {
            $this->clean($field, $suggestion);
        } catch (InvalidArgumentException) {
            return null;
        }

        return $suggestion;
    }

    /**
     * For fields that pick from a list: the options, and other words agents use for them.
     *
     * @return array{enum: class-string<BackedEnum>, words: array<string, BackedEnum>}|null
     */
    private function choices(ImportField $field): ?array
    {
        return match ($field) {
            ImportField::CivilStatus => ['enum' => CivilStatus::class, 'words' => ['widow' => CivilStatus::Widowed, 'widower' => CivilStatus::Widowed]],
            ImportField::Interest => ['enum' => LeadInterest::class, 'words' => ['car' => LeadInterest::Auto, 'motor' => LeadInterest::Auto, 'home' => LeadInterest::Property]],
            ImportField::PolicyType => ['enum' => PolicyType::class, 'words' => ['car' => PolicyType::Auto, 'motor' => PolicyType::Auto, 'home' => PolicyType::Property, 'fire' => PolicyType::Property, 'medical' => PolicyType::Health, 'hmo' => PolicyType::Health]],
            ImportField::PolicyStatus => ['enum' => PolicyStatus::class, 'words' => ['active' => PolicyStatus::InForce, 'inforce' => PolicyStatus::InForce, 'applied' => PolicyStatus::Pending, 'canceled' => PolicyStatus::Cancelled]],
            ImportField::PaymentFrequency => ['enum' => PaymentFrequency::class, 'words' => [
                'semi annual' => PaymentFrequency::SemiAnnual, 'semiannual' => PaymentFrequency::SemiAnnual, 'semi-annually' => PaymentFrequency::SemiAnnual,
                'annually' => PaymentFrequency::Annual, 'yearly' => PaymentFrequency::Annual,
                'one-time' => PaymentFrequency::Single, 'one time' => PaymentFrequency::Single,
            ]],
            default => null,
        };
    }

    /**
     * Moves the policy fields under "policy" when any is filled in, and checks the ones a policy needs.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, string>  $errors
     * @return array{0: array<string, mixed>, 1: array<string, string>}
     */
    private function splitPolicy(array $data, array $errors): array
    {
        $policy = [];
        $hasPolicy = false;

        foreach (ImportField::policyFields() as $field) {
            $hasPolicy = $hasPolicy || array_key_exists($field->value, $data) && $data[$field->value] !== null || isset($errors[$field->value]);
            $policy[$field->value] = $data[$field->value] ?? null;
            unset($data[$field->value]);
        }

        if (! $hasPolicy) {
            return [$data, $errors];
        }

        foreach ([ImportField::ProductName, ImportField::Premium, ImportField::PaymentFrequency] as $field) {
            if ($policy[$field->value] === null && ! isset($errors[$field->value])) {
                $errors[$field->value] = "A policy needs a {$this->lowerLabel($field)}.";
            }
        }

        $data['policy'] = array_filter($policy, fn (mixed $value) => $value !== null);

        return [$data, $errors];
    }

    private function lowerLabel(ImportField $field): string
    {
        return mb_strtolower($field->label());
    }
}
