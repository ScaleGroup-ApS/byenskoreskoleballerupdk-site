<?php

namespace App\Enums;

enum BookingStatus: string
{
    case Scheduled = 'scheduled';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';
}
