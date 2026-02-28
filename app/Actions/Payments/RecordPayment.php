<?php

namespace App\Actions\Payments;

use App\Events\PaymentRecorded;
use App\Models\Payment;
use App\Notifications\PaymentReceivedNotification;

class RecordPayment
{
    /**
     * @param  array{student_id: int, amount: float|string, method: string, notes?: string|null}  $data
     */
    public function handle(array $data): Payment
    {
        $payment = Payment::create($data);

        PaymentRecorded::fire(
            student_id: (int) $payment->student_id,
            payment_id: $payment->id,
            amount: (float) $payment->amount,
            method: $payment->method->value,
        );

        $payment->load('student.user');
        $payment->student->user->notify(new PaymentReceivedNotification($payment));

        return $payment;
    }
}
