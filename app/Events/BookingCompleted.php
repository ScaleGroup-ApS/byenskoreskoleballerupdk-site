<?php

namespace App\Events;

use App\States\StudentProgressionState;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;
use Thunk\Verbs\Event;

class BookingCompleted extends Event
{
    #[StateId(StudentProgressionState::class)]
    public int $student_id;

    public int $booking_id;

    public string $type;

    public function apply(StudentProgressionState $state): void
    {
        $state->lesson_counts[$this->type] = ($state->lesson_counts[$this->type] ?? 0) + 1;
    }
}
