<?php

namespace App\Actions\Enrollment;

use App\Enums\EnrollmentStatus;
use App\Events\EnrollmentRejected;
use App\Models\Enrollment;
use App\Notifications\EnrollmentRejectedNotification;

class RejectEnrollment
{
    public function handle(Enrollment $enrollment, string $rejectionReason): Enrollment
    {
        $enrollment->load('student.user');

        EnrollmentRejected::fire(
            enrollment_id: $enrollment->id,
            student_id: $enrollment->student_id,
            offer_id: $enrollment->offer_id,
            payment_method: $enrollment->payment_method->value,
            rejection_reason: $rejectionReason,
        );

        $enrollment->update([
            'status' => EnrollmentStatus::Rejected,
            'rejection_reason' => $rejectionReason,
        ]);

        $enrollment->student->user->notify(new EnrollmentRejectedNotification($enrollment));

        return $enrollment->refresh();
    }
}
