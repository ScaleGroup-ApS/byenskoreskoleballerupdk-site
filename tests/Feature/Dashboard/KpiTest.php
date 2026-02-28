<?php

use App\Actions\Dashboard\CalculateKpis;
use App\Actions\Offers\AssignOffer;
use App\Actions\Payments\RecordPayment;
use App\Enums\BookingStatus;
use App\Enums\StudentStatus;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\Offer;
use App\Models\Student;
use App\Models\User;
use Thunk\Verbs\Facades\Verbs;

test('admin sees total active students', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    Student::factory()->count(3)->create(['status' => StudentStatus::Active]);
    Student::factory()->create(['status' => StudentStatus::Inactive]);

    Verbs::commit();

    $kpis = (new CalculateKpis)->handle($admin);

    expect($kpis['total_students'])->toBe(3);
});

test('admin sees upcoming bookings in next 7 days', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $student = Student::factory()->create();
    $instructor = User::factory()->create(['role' => UserRole::Instructor]);

    Booking::factory()->create([
        'student_id' => $student->id,
        'instructor_id' => $instructor->id,
        'status' => BookingStatus::Scheduled,
        'starts_at' => now()->addDays(2),
        'ends_at' => now()->addDays(2)->addHour(),
    ]);

    // Outside 7-day window — should not be counted
    Booking::factory()->create([
        'student_id' => $student->id,
        'instructor_id' => $instructor->id,
        'status' => BookingStatus::Scheduled,
        'starts_at' => now()->addDays(10),
        'ends_at' => now()->addDays(10)->addHour(),
    ]);

    Verbs::commit();

    $kpis = (new CalculateKpis)->handle($admin);

    expect($kpis['upcoming_bookings'])->toBe(1);
});

test('admin no_show_rate is 0 when no bookings exist', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    Verbs::commit();

    $kpis = (new CalculateKpis)->handle($admin);

    expect($kpis['no_show_rate'])->toBe(0.0);
});

test('admin no_show_rate calculated from completed and no-show bookings', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $student = Student::factory()->create();
    $instructor = User::factory()->create(['role' => UserRole::Instructor]);

    Booking::factory()->count(3)->create([
        'student_id' => $student->id,
        'instructor_id' => $instructor->id,
        'status' => BookingStatus::Completed,
    ]);
    Booking::factory()->create([
        'student_id' => $student->id,
        'instructor_id' => $instructor->id,
        'status' => BookingStatus::NoShow,
    ]);

    Verbs::commit();

    $kpis = (new CalculateKpis)->handle($admin);

    expect($kpis['no_show_rate'])->toBe(25.0);
});

test('admin total outstanding reflects assigned offers minus payments', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $student = Student::factory()->create();
    $offer = Offer::factory()->create(['price' => 5000]);

    (new AssignOffer)->handle($student, $offer);
    (new RecordPayment)->handle([
        'student_id' => $student->id,
        'amount' => 2000,
        'method' => 'card',
        'notes' => null,
    ]);

    Verbs::commit();

    $kpis = (new CalculateKpis)->handle($admin);

    expect($kpis['total_outstanding'])->toBe(3000.0);
});

test('instructor only sees own upcoming bookings and no_show_rate', function () {
    $instructor = User::factory()->create(['role' => UserRole::Instructor]);
    $other = User::factory()->create(['role' => UserRole::Instructor]);
    $student = Student::factory()->create();

    Booking::factory()->create([
        'student_id' => $student->id,
        'instructor_id' => $instructor->id,
        'status' => BookingStatus::Scheduled,
        'starts_at' => now()->addDays(1),
        'ends_at' => now()->addDays(1)->addHour(),
    ]);
    Booking::factory()->create([
        'student_id' => $student->id,
        'instructor_id' => $other->id,
        'status' => BookingStatus::Scheduled,
        'starts_at' => now()->addDays(1)->addHours(2),
        'ends_at' => now()->addDays(1)->addHours(3),
    ]);

    Verbs::commit();

    $kpis = (new CalculateKpis)->handle($instructor);

    expect($kpis['upcoming_bookings'])->toBe(1);
    expect($kpis)->not->toHaveKey('total_students');
    expect($kpis)->not->toHaveKey('total_outstanding');
});
