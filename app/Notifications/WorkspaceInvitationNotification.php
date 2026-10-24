<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\WorkspaceInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkspaceInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public WorkspaceInvitation $invitation, public string $token) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $inviter = $this->invitation->inviter?->name ?? 'Someone';

        return (new MailMessage)
            ->subject("Join {$this->invitation->workspace->name} on Insurix")
            ->line("{$inviter} invited you to help manage {$this->invitation->workspace->name} on Insurix.")
            ->action('Accept the invite', config('app.frontend_url')."/invitations/{$this->token}")
            ->line('The link works for 7 days. Sign in or create an account with this email address to accept it.');
    }
}
