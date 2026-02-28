<?php

namespace App\Actions\Payments;

use App\Models\Student;
use App\States\StudentBalanceState;

class CalculateBalance
{
    /**
     * @return array{total_owed: float, total_paid: float, outstanding: float}
     */
    public function handle(Student $student): array
    {
        $state = StudentBalanceState::load($student->id);

        $outstanding = $state->total_owed - $state->total_paid;

        return [
            'total_owed' => $state->total_owed,
            'total_paid' => $state->total_paid,
            'outstanding' => $outstanding,
        ];
    }
}
