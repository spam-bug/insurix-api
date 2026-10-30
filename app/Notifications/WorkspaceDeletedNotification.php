<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

/**
 * Tells a member that a workspace they help in was deleted. By email: they've lost access to
 * it, so its bell is gone too. Names are passed as text, since the workspace may be gone by
 * the time the queue sends this.
 */
class WorkspaceDeletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  Carbon|null  $restorableUntil  when the owner can still bring it back; null when it's gone for good
     */
    public function __construct(
        public string $workspaceName,
        public string $ownerName,
        public ?Carbon $restorableUntil,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject("{$this->workspaceName} was deleted")
            ->line("{$this->ownerName} deleted {$this->workspaceName} on Insurix, so you no longer have access to it.");

        return $this->restorableUntil
            ? $message->line("They can restore it until {$this->restorableUntil->toFormattedDateString()}. After that, its data is removed for good.")
            : $message->line('It was deleted along with their account, and its data has been removed.');
    }
}
