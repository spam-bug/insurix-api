<?php

declare(strict_types=1);

namespace App\Enums;

use App\Models\Lead;
use BackedEnum;

/** A detail of the lead that a condition checks. */
enum AutomationField: string
{
    case Source = 'source';
    case Interest = 'interest';
    case Stage = 'stage';
    case PreferredChannel = 'preferred_channel';
    case Score = 'score';
    case DaysSinceAdded = 'days_since_added';
    case Email = 'email';
    case Phone = 'phone';

    /**
     * The comparisons that make sense for this field: a choice is or isn't something, a number
     * is at least or at most something, contact details are there or not.
     *
     * @return array<int, AutomationOperator>
     */
    public function operators(): array
    {
        return match ($this) {
            self::Source, self::Interest, self::Stage, self::PreferredChannel => [AutomationOperator::Is, AutomationOperator::IsNot],
            self::Score, self::DaysSinceAdded => [AutomationOperator::AtLeast, AutomationOperator::AtMost],
            self::Email, self::Phone => [AutomationOperator::IsSet, AutomationOperator::IsNotSet],
        };
    }

    /**
     * The enum a choice field's value must be one of, so a rule can't compare against nonsense.
     *
     * @return class-string<BackedEnum>|null
     */
    public function options(): ?string
    {
        return match ($this) {
            self::Source => LeadSource::class,
            self::Interest => LeadInterest::class,
            self::Stage => LeadStatus::class,
            self::PreferredChannel => ContactChannel::class,
            default => null,
        };
    }

    public function matches(Lead $lead, AutomationOperator $operator, mixed $value): bool
    {
        $actual = match ($this) {
            self::Source => $lead->source->value,
            self::Interest => $lead->interest?->value,
            self::Stage => $lead->status->value,
            self::PreferredChannel => $lead->preferred_channel?->value,
            self::Score => $lead->score ?? 0,
            self::DaysSinceAdded => (int) $lead->created_at->diffInDays(now()),
            self::Email => $lead->email,
            self::Phone => $lead->phone,
        };

        return match ($operator) {
            AutomationOperator::Is => $actual === $value,
            AutomationOperator::IsNot => $actual !== $value,
            AutomationOperator::AtLeast => $actual >= (int) $value,
            AutomationOperator::AtMost => $actual <= (int) $value,
            AutomationOperator::IsSet => filled($actual),
            AutomationOperator::IsNotSet => blank($actual),
        };
    }
}
