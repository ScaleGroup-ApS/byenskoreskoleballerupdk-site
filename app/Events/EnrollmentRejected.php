<?php

namespace App\Events;

use App\States\EnrollmentState;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;
use Thunk\Verbs\Event;

class EnrollmentRejected extends Event
{
    #[StateId(EnrollmentState::class)]
    public int $enrollment_id;

    public int $student_id;

    public int $offer_id;

    public string $payment_method;

    public string $rejection_reason;

    public function apply(EnrollmentState $state): void
    {
        $state->status = 'rejected';
    }
}
