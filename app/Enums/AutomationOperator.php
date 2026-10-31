<?php

declare(strict_types=1);

namespace App\Enums;

enum AutomationOperator: string
{
    case Is = 'is';
    case IsNot = 'is_not';
    case AtLeast = 'at_least';
    case AtMost = 'at_most';
    case IsSet = 'is_set';
    case IsNotSet = 'is_not_set';

    /** Whether the condition compares against a value, rather than only checking something is there. */
    public function needsValue(): bool
    {
        return ! in_array($this, [self::IsSet, self::IsNotSet], true);
    }
}
