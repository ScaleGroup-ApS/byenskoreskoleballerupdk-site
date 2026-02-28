<?php

namespace App\States;

use Thunk\Verbs\State;

class StudentBalanceState extends State
{
    public float $total_owed = 0.0;

    public float $total_paid = 0.0;
}
