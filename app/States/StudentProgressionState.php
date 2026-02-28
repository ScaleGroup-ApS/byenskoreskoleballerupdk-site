<?php

namespace App\States;

use Thunk\Verbs\State;

class StudentProgressionState extends State
{
    public ?string $enrolled_at = null;

    public array $lesson_counts = [];
}
