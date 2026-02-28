<?php

namespace App\States;

use Thunk\Verbs\State;

class EnrollmentState extends State
{
    public string $status = 'pending';

    public string $payment_method = '';
}
