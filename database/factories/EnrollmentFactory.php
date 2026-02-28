<?php

namespace Database\Factories;

use App\Enums\EnrollmentPaymentMethod;
use App\Enums\EnrollmentStatus;
use App\Models\Offer;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Enrollment>
 */
class EnrollmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'offer_id' => Offer::factory(),
            'course_id' => null,
            'payment_method' => EnrollmentPaymentMethod::Cash,
            'status' => EnrollmentStatus::PendingApproval,
            'stripe_session_id' => null,
            'rejection_reason' => null,
        ];
    }
}
