<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingCancelledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Booking $booking) {}

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
            ->subject('Din booking er annulleret')
            ->greeting("Hej {$notifiable->name}!")
            ->line("Din booking ({$this->booking->type->value}) d. {$this->booking->starts_at->format('d/m/Y H:i')} er blevet annulleret.")
            ->action('Se dine bookinger', url('/bookings'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'type' => $this->booking->type->value,
            'starts_at' => $this->booking->starts_at->toISOString(),
        ];
    }
}
