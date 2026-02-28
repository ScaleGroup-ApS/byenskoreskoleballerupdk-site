<?php

namespace App\Actions\Bookings;

use App\Models\Booking;
use App\Models\Student;
use App\Models\User;
use App\Models\Vehicle;

class CheckBookingConflicts
{
    /**
     * @return string[]
     */
    public function handle(
        string $startsAt,
        string $endsAt,
        ?User $instructor,
        Student $student,
        ?Vehicle $vehicle = null,
        ?int $excludeBookingId = null,
    ): array {
        $conflicts = [];

        if ($instructor !== null) {
            $instructorConflict = Booking::query()
                ->where('instructor_id', $instructor->id)
                ->overlapping($startsAt, $endsAt)
                ->when($excludeBookingId, fn ($q) => $q->where('id', '!=', $excludeBookingId))
                ->exists();

            if ($instructorConflict) {
                $conflicts[] = 'Instruktøren har allerede en booking i dette tidsrum.';
            }
        }

        $studentConflict = Booking::query()
            ->where('student_id', $student->id)
            ->overlapping($startsAt, $endsAt)
            ->when($excludeBookingId, fn ($q) => $q->where('id', '!=', $excludeBookingId))
            ->exists();

        if ($studentConflict) {
            $conflicts[] = 'Eleven har allerede en booking i dette tidsrum.';
        }

        if ($vehicle !== null) {
            $vehicleConflict = Booking::query()
                ->where('vehicle_id', $vehicle->id)
                ->overlapping($startsAt, $endsAt)
                ->when($excludeBookingId, fn ($q) => $q->where('id', '!=', $excludeBookingId))
                ->exists();

            if ($vehicleConflict) {
                $conflicts[] = 'Køretøjet er allerede booket i dette tidsrum.';
            }
        }

        return $conflicts;
    }
}
