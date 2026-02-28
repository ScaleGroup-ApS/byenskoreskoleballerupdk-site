<?php

namespace App\Enums;

enum EnrollmentStatus: string
{
    case PendingPayment = 'pending_payment';
    case PendingApproval = 'pending_approval';
    case Completed = 'completed';
    case Rejected = 'rejected';
}
