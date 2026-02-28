<?php

namespace App\Actions\Enrollment;

use App\Actions\Offers\AssignOffer;
use App\Actions\Payments\RecordPayment;
use App\Enums\EnrollmentStatus;
use App\Events\EnrollmentApproved;
use App\Models\Enrollment;
use App\Models\User;
use App\Notifications\EnrollmentApprovedNotification;

class ApproveEnrollment
{
    public function __construct(
        private readonly AssignOffer $assignOffer,
        private readonly CreateEnrollmentBooking $createEnrollmentBooking,
        private readonly RecordPayment $recordPayment,
    ) {}

    public function handle(Enrollment $enrollment, User $approvedBy): Enrollment
    {
        $enrollment->load(['student.user', 'offer']);

        $this->assignOffer->handle($enrollment->student, $enrollment->offer);

        $this->recordPayment->handle([
            'student_id' => $enrollment->student_id,
            'amount' => $enrollment->offer->price,
            'method' => $enrollment->payment_method->value,
            'notes' => null,
        ]);

        EnrollmentApproved::fire(
            enrollment_id: $enrollment->id,
            student_id: $enrollment->student_id,
            offer_id: $enrollment->offer_id,
            payment_method: $enrollment->payment_method->value,
        );

        $enrollment->update([
            'status' => EnrollmentStatus::Completed,
        ]);

        $this->createEnrollmentBooking->handle($enrollment);

        $enrollment->student->user->notify(new EnrollmentApprovedNotification($enrollment));

        return $enrollment->refresh();
    }
}
