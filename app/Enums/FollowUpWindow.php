<?php

namespace App\Enums;

/** Which slice of the agent's follow-ups to list. Day boundaries use the agent's timezone. */
enum FollowUpWindow: string
{
    /** Open and due by the end of today, overdue ones included. */
    case Today = 'today';

    /** Open and due after today. */
    case Upcoming = 'upcoming';

    /** Every open follow-up. */
    case Open = 'open';

    case Completed = 'completed';
}
