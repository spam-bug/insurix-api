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
use InvalidArgumentException;

class CleanImportRow
{
    /**
     * Reads one row through the agent's column choices. Values that can't be read become errors
     * on their field; the rest are cleaned. A client row with any policy column filled in also
     * describes a policy, which then needs a product, a premium and a payment frequency.
     *
     * @param  array<string, int>  $mapping  field => column position
     * @param  array<int, string>  $row
     * @return array{values: array<string, string>, data: array<string, mixed>, errors: array<string, string>}
     */
    public function handle(ImportKind $kind, array $mapping, array $row): array
    {
        $values = [];
        $data = [];
        $errors = [];

        foreach ($mapping as $field => $column) {
            $values[$field] = $row[$column] ?? '';
        }

        foreach ($values as $field => $value) {
            try {
                $data[$field] = $this->clean(ImportField::from($field), $value);
            } catch (InvalidArgumentException $exception) {
                $errors[$field] = $exception->getMessage();
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

        return ['values' => $values, 'data' => array_filter($data, fn (mixed $value) => $value !== null), 'errors' => $errors];
    }

    private function clean(ImportField $field, string $value): mixed
    {
        return match ($field) {
            ImportField::Email => ImportValue::email($value),
            ImportField::Phone => ImportValue::phone($value),
            ImportField::Birthdate, ImportField::EffectiveDate, ImportField::RenewalDate => ImportValue::date($value),
            ImportField::Premium, ImportField::CoverageAmount => ImportValue::money($value),
            ImportField::CommissionRate => ImportValue::percent($value),
            ImportField::CivilStatus => ImportValue::choice($value, CivilStatus::class, ['widow' => CivilStatus::Widowed, 'widower' => CivilStatus::Widowed])?->value,
            ImportField::Interest => ImportValue::choice($value, LeadInterest::class, ['car' => LeadInterest::Auto, 'motor' => LeadInterest::Auto, 'home' => LeadInterest::Property])?->value,
            ImportField::PolicyType => ImportValue::choice($value, PolicyType::class, ['car' => PolicyType::Auto, 'motor' => PolicyType::Auto, 'home' => PolicyType::Property, 'fire' => PolicyType::Property, 'medical' => PolicyType::Health, 'hmo' => PolicyType::Health])?->value,
            ImportField::PolicyStatus => ImportValue::choice($value, PolicyStatus::class, ['active' => PolicyStatus::InForce, 'inforce' => PolicyStatus::InForce, 'applied' => PolicyStatus::Pending, 'canceled' => PolicyStatus::Cancelled])?->value,
            ImportField::PaymentFrequency => ImportValue::choice($value, PaymentFrequency::class, [
                'monthly' => PaymentFrequency::Monthly, 'quarterly' => PaymentFrequency::Quarterly,
                'semi annual' => PaymentFrequency::SemiAnnual, 'semiannual' => PaymentFrequency::SemiAnnual, 'semi-annually' => PaymentFrequency::SemiAnnual,
                'annually' => PaymentFrequency::Annual, 'yearly' => PaymentFrequency::Annual,
                'single' => PaymentFrequency::Single, 'one-time' => PaymentFrequency::Single, 'one time' => PaymentFrequency::Single,
            ])?->value,
            ImportField::Notes => ImportValue::text($value, 2000),
            default => ImportValue::text($value),
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
