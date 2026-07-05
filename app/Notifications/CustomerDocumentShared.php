<?php

namespace App\Notifications;

use App\Models\CustomerDocument;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Sent to a customer when staff share a new document with them. */
class CustomerDocumentShared extends Notification
{
    public function __construct(public CustomerDocument $document) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('A new document was shared with you')
            ->greeting('Hello ' . ($notifiable->name ?? 'there') . ',')
            ->line('A new document has been shared with your account: ' . $this->document->name)
            ->action('View in portal', route('portal.dashboard'))
            ->line('Sign in to your customer portal to download it.');
    }
}
