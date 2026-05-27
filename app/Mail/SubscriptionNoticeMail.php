<?php

/**
 * Subscription notice email.
 * Sends reminders or important subscription notices.
 */

namespace App\Mail;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionNoticeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Subscription $subscription,
        public string $subjectLine,
        public string $title,
        public string $bodyText,
    ) {
        $this->subscription->loadMissing('plan', 'user');
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.subscriptions.notice');
    }
}
