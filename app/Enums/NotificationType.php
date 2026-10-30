<?php

declare(strict_types=1);

namespace App\Enums;

/** What a bell notification is about; stored in the notifications table's type column. */
enum NotificationType: string
{
    case FormLead = 'form_lead';
    case FormResponse = 'form_response';
    case RenewalComingUp = 'renewal_coming_up';
    case FollowUpsDue = 'follow_ups_due';
    case MemberJoined = 'member_joined';
    case StorageAlmostFull = 'storage_almost_full';
    case Mention = 'mention';
    case ExportReady = 'export_ready';

    /** The short tag shown on the notification. */
    public function label(): string
    {
        return match ($this) {
            self::FormLead => 'Lead',
            self::FormResponse => 'Form',
            self::RenewalComingUp => 'Renewal',
            self::FollowUpsDue => 'Follow-ups',
            self::MemberJoined => 'Team',
            self::StorageAlmostFull => 'Storage',
            self::Mention => 'Mention',
            self::ExportReady => 'Export',
        };
    }
}
