<?php

use App\Actions\Bookings\CompleteBooking;
use App\Actions\Offers\AssignOffer;
use App\Actions\Progression\CheckExamReadiness;
use App\Models\Booking;
use App\Models\Offer;
use App\Models\Student;
use Thunk\Verbs\Facades\Verbs;

test('student with no offers is ready (no requirements)', function () {
    $student = Student::factory()->create();

    Verbs::commit();

    $result = (new CheckExamReadiness)->handle($student);

    expect($result['is_ready'])->toBeTrue();
    expect($result['missing'])->toBeEmpty();
});

test('student is not ready when driving lessons are incomplete', function () {
    $student = Student::factory()->create();
    $offer = Offer::factory()->create([
        'driving_lessons' => 25,
        'theory_lessons' => 0,
        'track_required' => false,
        'slippery_required' => false,
    ]);

    (new AssignOffer)->handle($student, $offer);

    // Complete only 10 out of 25
    for ($i = 0; $i < 10; $i++) {
        $booking = Booking::factory()->create([
            'student_id' => $student->id,
            'type' => 'driving_lesson',
        ]);
        (new CompleteBooking)->handle($booking);
    }

    Verbs::commit();

    $result = (new CheckExamReadiness)->handle($student);

    expect($result['is_ready'])->toBeFalse();
    expect($result['missing']['driving_lesson'])->toBe(15);
});

test('student is ready when all requirements are met', function () {
    $student = Student::factory()->create();
    $offer = Offer::factory()->create([
        'driving_lessons' => 2,
        'theory_lessons' => 1,
        'track_required' => false,
        'slippery_required' => false,
    ]);

    (new AssignOffer)->handle($student, $offer);

    foreach (['driving_lesson', 'driving_lesson', 'theory_lesson'] as $type) {
        $booking = Booking::factory()->create([
            'student_id' => $student->id,
            'type' => $type,
        ]);
        (new CompleteBooking)->handle($booking);
    }

    Verbs::commit();

    $result = (new CheckExamReadiness)->handle($student);

    expect($result['is_ready'])->toBeTrue();
    expect($result['missing'])->toBeEmpty();
});

test('track and slippery required flags are included in requirements', function () {
    $student = Student::factory()->create();
    $offer = Offer::factory()->create([
        'driving_lessons' => 0,
        'theory_lessons' => 0,
        'track_required' => true,
        'slippery_required' => true,
    ]);

    (new AssignOffer)->handle($student, $offer);

    Verbs::commit();

    $result = (new CheckExamReadiness)->handle($student);

    expect($result['is_ready'])->toBeFalse();
    expect($result['missing'])->toHaveKeys(['track_driving', 'slippery_driving']);
});

test('multiple offers accumulate driving and theory requirements', function () {
    $student = Student::factory()->create();
    $primary = Offer::factory()->create(['driving_lessons' => 20, 'theory_lessons' => 10, 'track_required' => false, 'slippery_required' => false]);
    $addon = Offer::factory()->create(['driving_lessons' => 5, 'theory_lessons' => 0, 'track_required' => false, 'slippery_required' => false]);

    (new AssignOffer)->handle($student, $primary);
    (new AssignOffer)->handle($student, $addon);

    Verbs::commit();

    $result = (new CheckExamReadiness)->handle($student);

    expect($result['required']['driving_lesson'])->toBe(25);
    expect($result['required']['theory_lesson'])->toBe(10);
});
