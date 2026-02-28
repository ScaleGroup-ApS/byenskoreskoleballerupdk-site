<?php

namespace App\Jobs;

use App\Actions\Enrollment\CreateEnrollmentBooking;
use App\Enums\EnrollmentStatus;
use App\Models\Booking;
use App\Models\Enrollment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CreateEnrollmentBookings implements ShouldQueue
{
    use Queueable;

    public function handle(CreateEnrollmentBooking $createEnrollmentBooking): void
    {
        Enrollment::query()
            ->whereIn('status', [EnrollmentStatus::Completed->value, EnrollmentStatus::PendingApproval->value])
            ->with(['course'])
            ->whereHas('course', fn ($q) => $q->where('start_at', '>=', now()))
            ->each(function (Enrollment $enrollment) use ($createEnrollmentBooking): void {
                $alreadyBooked = Booking::query()
                    ->where('student_id', $enrollment->student_id)
                    ->where('starts_at', $enrollment->course->start_at)
                    ->exists();

                if (! $alreadyBooked) {
                    $createEnrollmentBooking->handle($enrollment);
                }
            });
    }
}
