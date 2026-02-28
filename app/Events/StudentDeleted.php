<?php

namespace App\Events;

use App\States\StudentProgressionState;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;
use Thunk\Verbs\Event;

class StudentDeleted extends Event
{
    #[StateId(StudentProgressionState::class)]
    public int $student_id;

    public string $student_name;
}
