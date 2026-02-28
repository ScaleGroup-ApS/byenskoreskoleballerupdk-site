<?php

namespace App\Events;

use App\States\StudentBalanceState;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;
use Thunk\Verbs\Event;

class PaymentRecorded extends Event
{
    #[StateId(StudentBalanceState::class)]
    public int $student_id;

    public int $payment_id;

    public float $amount;

    public string $method;

    public function apply(StudentBalanceState $state): void
    {
        $state->total_paid += $this->amount;
    }
}
