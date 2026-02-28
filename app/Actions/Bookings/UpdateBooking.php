<?php

namespace App\Actions\Bookings;

use App\Events\BookingUpdated;
use App\Models\Booking;

class UpdateBooking
{
    /**
     * @param  array{instructor_id?: int|null, vehicle_id?: int|null, starts_at?: string, ends_at?: string, notes?: string|null}  $data
     */
    public function handle(Booking $booking, array $data): Booking
    {
        if (array_key_exists('instructor_id', $data)) {
            $booking->instructor_id = $data['instructor_id'];
        }

        if (array_key_exists('vehicle_id', $data)) {
            $booking->vehicle_id = $data['vehicle_id'];
        }

        $booking->starts_at = $data['starts_at'] ?? $booking->starts_at;
        $booking->ends_at = $data['ends_at'] ?? $booking->ends_at;
        $booking->notes = $data['notes'] ?? $booking->notes;
        $booking->save();

        BookingUpdated::fire(
            student_id: $booking->student_id,
            booking_id: $booking->id,
            starts_at: $booking->starts_at->toDateTimeString(),
            ends_at: $booking->ends_at->toDateTimeString(),
        );

        return $booking->refresh();
    }
}
