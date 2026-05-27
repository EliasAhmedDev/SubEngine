<?php

/**
 * Mail sent when subscription is activated.
 * Welcomes users and confirms activation details.
 */

namespace App\Mail;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionActivatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Subscription $subscription)
    {
        $this->subscription->loadMissing('plan', 'user');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your subscription is active',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.subscriptions.activated',
        );
    }
}
