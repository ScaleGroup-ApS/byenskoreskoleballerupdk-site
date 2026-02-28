<?php

namespace App\Events;

use App\States\StudentProgressionState;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;
use Thunk\Verbs\Event;

class StudentEnrolled extends Event
{
    #[StateId(StudentProgressionState::class)]
    public int $student_id;

    public string $student_name;

    public string $start_date;

    public function apply(StudentProgressionState $state): void
    {
        $state->enrolled_at = $this->start_date;
        $state->lesson_counts = [];
    }
}
