<?php

namespace App\Notifications;

use App\Models\Enrollment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EnrollmentRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Enrollment $enrollment) {}

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
            ->subject('Din tilmelding er afvist')
            ->greeting("Hej {$notifiable->name}!")
            ->line("Din tilmelding til {$this->enrollment->offer->name} er desværre blevet afvist.")
            ->when(
                $this->enrollment->rejection_reason,
                fn (MailMessage $mail) => $mail->line('Årsag: '.$this->enrollment->rejection_reason),
            )
            ->line('Kontakt os venligst for mere information.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'enrollment_id' => $this->enrollment->id,
            'offer_name' => $this->enrollment->offer->name,
            'rejection_reason' => $this->enrollment->rejection_reason,
        ];
    }
}
