<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $senderName,
        public readonly string $conversationId,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Ny besked fra {$this->senderName}")
            ->greeting("Hej {$notifiable->name}!")
            ->line("Du har modtaget en ny besked fra {$this->senderName}.")
            ->action('Læs besked', url('/chat'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'sender_name' => $this->senderName,
            'conversation_id' => $this->conversationId,
        ];
    }
}
