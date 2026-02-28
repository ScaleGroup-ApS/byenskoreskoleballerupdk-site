<?php

namespace App\Events;

use App\States\StudentProgressionState;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;
use Thunk\Verbs\Event;

class BookingCancelled extends Event
{
    #[StateId(StudentProgressionState::class)]
    public int $student_id;

    public int $booking_id;

    public string $type;

    public string $reason;

    public function apply(StudentProgressionState $state): void
    {
        $current = $state->lesson_counts[$this->type] ?? 0;

        if ($current > 0) {
            $state->lesson_counts[$this->type] = $current - 1;
        }
    }
}
