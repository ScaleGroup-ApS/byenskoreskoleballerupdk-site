<?php

namespace App\Actions\Enrollment;

use App\Enums\EnrollmentPaymentMethod;
use App\Enums\EnrollmentStatus;
use App\Enums\StudentStatus;
use App\Enums\UserRole;
use App\Events\EnrollmentRequested;
use App\Events\StudentEnrolled;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Offer;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class InitiateEnrollment
{
    /**
     * @param  array{name: string, email: string, password: string, phone?: string|null, cpr?: string|null, course_id: int, payment_method: string}  $data
     * @return array{0: Student, 1: Enrollment}
     */
    public function handle(array $data, Offer $offer): array
    {
        return DB::transaction(function () use ($data, $offer) {
            $course = Course::findOrFail($data['course_id']);

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => UserRole::Student,
            ]);

            $student = Student::create([
                'user_id' => $user->id,
                'phone' => $data['phone'] ?? null,
                'cpr' => $data['cpr'] ?? null,
                'status' => StudentStatus::Active,
                'start_date' => $course->start_at->toDateString(),
            ]);

            StudentEnrolled::fire(
                student_id: $student->id,
                student_name: $user->name,
                start_date: $student->start_date->toDateString(),
            );

            $paymentMethod = EnrollmentPaymentMethod::from($data['payment_method']);

            $status = $paymentMethod === EnrollmentPaymentMethod::Stripe
                ? EnrollmentStatus::PendingPayment
                : EnrollmentStatus::PendingApproval;

            $enrollment = Enrollment::create([
                'student_id' => $student->id,
                'offer_id' => $offer->id,
                'course_id' => $course->id,
                'payment_method' => $paymentMethod,
                'status' => $status,
            ]);

            EnrollmentRequested::fire(
                enrollment_id: $enrollment->id,
                student_id: $student->id,
                offer_id: $offer->id,
                payment_method: $paymentMethod->value,
            );

            return [$student, $enrollment];
        });
    }
}
