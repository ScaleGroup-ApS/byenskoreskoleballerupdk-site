<?php

namespace App\Events;

use App\States\StudentProgressionState;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;
use Thunk\Verbs\Event;

class BookingCreated extends Event
{
    #[StateId(StudentProgressionState::class)]
    public int $student_id;

    public int $booking_id;

    public string $type;

    public string $starts_at;
}
