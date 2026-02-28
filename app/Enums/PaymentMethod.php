<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Card = 'card';
    case MobilePay = 'mobile_pay';
    case Invoice = 'invoice';
}
