<?php

/**
 * Notification for subscription lifecycle events.
 * Sends lifecycle updates to users or systems.
 */

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionLifecycleNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [30, 120, 300];

    public function __construct(
        public string $subjectLine,
        public string $headline,
        public string $bodyLine,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subjectLine)
            ->greeting("Hi {$notifiable->name},")
            ->line($this->headline)
            ->line($this->bodyLine)
            ->salutation('Thanks for using SubEngine.');
    }
}
