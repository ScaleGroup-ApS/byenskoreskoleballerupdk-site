<?php

namespace App\Events;

use App\States\StudentProgressionState;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;
use Thunk\Verbs\Event;

class StudentStatusChanged extends Event
{
    #[StateId(StudentProgressionState::class)]
    public int $student_id;

    public string $old_status;

    public string $new_status;
}
