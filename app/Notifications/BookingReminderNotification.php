<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingReminderNotification extends Notification implements ShouldQueue
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
            ->subject('Påmindelse om booking i morgen')
            ->greeting("Hej {$notifiable->name}!")
            ->line("Du har en booking i morgen: {$this->booking->type->value}")
            ->line("Tidspunkt: {$this->booking->starts_at->format('H:i')} — {$this->booking->ends_at->format('H:i')}")
            ->action('Se booking', url('/bookings'))
            ->line('Husk at møde til tiden.');
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
