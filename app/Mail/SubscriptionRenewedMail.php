<?php

/**
 * Mail sent when subscription is renewed.
 * Notifies users about successful renewals.
 */

namespace App\Mail;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionRenewedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Subscription $subscription)
    {
        $this->subscription->loadMissing('plan', 'user');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your subscription was renewed',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.subscriptions.renewed',
        );
    }
}
