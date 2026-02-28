<?php

namespace App\Jobs;

use App\Enums\BookingStatus;
use App\Events\BookingNoShow;
use App\Models\Booking;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class FlagNoShows implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        Booking::query()
            ->where('status', BookingStatus::Scheduled)
            ->where('ends_at', '<', now())
            ->each(function (Booking $booking) {
                $booking->update(['status' => BookingStatus::NoShow]);

                BookingNoShow::fire(
                    student_id: $booking->student_id,
                    booking_id: $booking->id,
                    type: $booking->type->value,
                );
            });
    }
}
