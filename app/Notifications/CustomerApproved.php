<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Sent to a customer when staff activate (approve) their portal account. */
class CustomerApproved extends Notification
{
    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your PharmaTrack account is approved')
            ->greeting('Hello ' . ($notifiable->name ?? 'there') . ',')
            ->line('Good news — your customer account has been approved and is now active.')
            ->line('You can sign in to the customer portal to track your orders, invoices and documents.')
            ->action('Go to Customer Portal', route('portal.login'))
            ->line('If you have not set a password yet, use "Forgot password" on the login page.');
    }
}
