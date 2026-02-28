<?php

namespace App\Actions\Enrollment;

use App\Actions\Offers\AssignOffer;
use App\Actions\Payments\RecordPayment;
use App\Enums\EnrollmentStatus;
use App\Events\StripePaymentCompleted;
use App\Models\Enrollment;
use Illuminate\Validation\ValidationException;
use Stripe\Checkout\Session;
use Stripe\Stripe;

class CompleteStripeEnrollment
{
    public function __construct(
        private readonly AssignOffer $assignOffer,
        private readonly RecordPayment $recordPayment,
        private readonly CreateEnrollmentBooking $createEnrollmentBooking,
    ) {}

    public function handle(string $sessionId): Enrollment
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $session = Session::retrieve($sessionId);

        if ($session->payment_status !== 'paid') {
            throw ValidationException::withMessages([
                'session' => 'Betalingen er ikke gennemført endnu.',
            ]);
        }

        $enrollment = Enrollment::with(['student', 'offer'])
            ->findOrFail($session->metadata->enrollment_id);

        if ($enrollment->status === EnrollmentStatus::Completed) {
            return $enrollment;
        }

        $this->assignOffer->handle($enrollment->student, $enrollment->offer);

        $this->recordPayment->handle([
            'student_id' => $enrollment->student_id,
            'amount' => $enrollment->offer->price,
            'method' => 'card',
            'notes' => 'Stripe Checkout Session: '.$sessionId,
        ]);

        StripePaymentCompleted::fire(
            enrollment_id: $enrollment->id,
            student_id: $enrollment->student_id,
            offer_id: $enrollment->offer_id,
            payment_method: $enrollment->payment_method->value,
        );

        $enrollment->update(['status' => EnrollmentStatus::Completed]);

        $this->createEnrollmentBooking->handle($enrollment);

        return $enrollment->refresh();
    }
}
