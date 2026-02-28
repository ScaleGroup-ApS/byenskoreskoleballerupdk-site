<?php

namespace App\Jobs;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Notifications\BookingReminderNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendBookingReminder implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        Booking::query()
            ->where('status', BookingStatus::Scheduled)
            ->whereBetween('starts_at', [now()->addHours(23), now()->addHours(25)])
            ->with(['student.user', 'instructor'])
            ->each(function (Booking $booking) {
                $booking->student->user->notify(new BookingReminderNotification($booking));
                $booking->instructor->notify(new BookingReminderNotification($booking));
            });
    }
}
