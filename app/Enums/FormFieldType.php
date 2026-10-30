<?php

declare(strict_types=1);

namespace App\Enums;

/** What a form block asks for. Layout blocks (heading, paragraph, divider) ask nothing. */
enum FormFieldType: string
{
    case ShortText = 'short_text';
    case LongText = 'long_text';
    case Email = 'email';
    case Phone = 'phone';
    case Number = 'number';
    case Date = 'date';
    case Dropdown = 'dropdown';
    case SingleChoice = 'single_choice';
    case MultipleChoice = 'multiple_choice';
    case YesNo = 'yes_no';
    case Consent = 'consent';
    case Heading = 'heading';
    case Paragraph = 'paragraph';
    case Divider = 'divider';

    public function label(): string
    {
        return match ($this) {
            self::ShortText => 'Short answer',
            self::LongText => 'Long answer',
            self::Email => 'Email',
            self::Phone => 'Phone',
            self::Number => 'Number',
            self::Date => 'Date',
            self::Dropdown => 'Dropdown',
            self::SingleChoice => 'Single choice',
            self::MultipleChoice => 'Multiple choice',
            self::YesNo => 'Yes or no',
            self::Consent => 'Consent checkbox',
            self::Heading => 'Heading',
            self::Paragraph => 'Paragraph',
            self::Divider => 'Divider',
        };
    }

    public function isLayout(): bool
    {
        return in_array($this, [self::Heading, self::Paragraph, self::Divider], true);
    }

    public function hasOptions(): bool
    {
        return in_array($this, [self::Dropdown, self::SingleChoice, self::MultipleChoice], true);
    }
}
