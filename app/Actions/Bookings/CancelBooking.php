<?php

namespace App\Actions\Bookings;

use App\Enums\BookingStatus;
use App\Events\BookingCancelled;
use App\Models\Booking;
use App\Notifications\BookingCancelledNotification;

class CancelBooking
{
    public function handle(Booking $booking, string $reason = ''): Booking
    {
        $booking->status = BookingStatus::Cancelled;
        $booking->save();

        BookingCancelled::fire(
            student_id: $booking->student_id,
            booking_id: $booking->id,
            type: $booking->type->value,
            reason: $reason,
        );

        $booking->load('student.user');
        $booking->student->user->notify(new BookingCancelledNotification($booking));

        return $booking->refresh();
    }
}
