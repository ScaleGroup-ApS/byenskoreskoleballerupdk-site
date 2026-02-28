<?php

namespace App\Enums;

enum EnrollmentPaymentMethod: string
{
    case Stripe = 'stripe';
    case Cash = 'cash';
}
