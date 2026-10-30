<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

/** Laravel's verification email, queued, in Insurix's words. */
class VerifyEmailNotification extends VerifyEmail implements ShouldQueue
{
    use Queueable;

    protected function buildMailMessage($url): MailMessage
    {
        return (new MailMessage)
            ->subject('Confirm your email for Insurix')
            ->line('Confirm this is your email address, so password resets and invites reach you.')
            ->action('Confirm email', $url)
            ->line('The link works for 24 hours. If you didn’t create an Insurix account, you can ignore this email.');
    }
}
