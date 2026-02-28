<?php

namespace App\Events;

use App\States\EnrollmentState;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;
use Thunk\Verbs\Event;

class EnrollmentApproved extends Event
{
    #[StateId(EnrollmentState::class)]
    public int $enrollment_id;

    public int $student_id;

    public int $offer_id;

    public string $payment_method;

    public function apply(EnrollmentState $state): void
    {
        $state->status = 'completed';
    }
}
