<?php

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Offer;
use App\Models\Student;

function validEnrollmentData(int $courseId): array
{
    return [
        'name' => 'Test Elev',
        'email' => 'elev@example.com',
        'phone' => '+45 12 34 56 78',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'payment_method' => 'cash',
        'course_id' => $courseId,
    ];
}

test('student can enroll with a valid course_id', function () {
    $offer = Offer::factory()->create();
    $startAt = now()->addWeeks(2);
    $course = Course::factory()->for($offer)->create([
        'start_at' => $startAt,
        'end_at' => $startAt->copy()->addHours(8),
    ]);

    $this->post(route('enrollment.store', $offer), validEnrollmentData($course->id))
        ->assertRedirect(route('dashboard'));

    $enrollment = Enrollment::first();
    expect($enrollment)->not->toBeNull();
    expect($enrollment->course_id)->toBe($course->id);

    $student = Student::first();
    expect($student->start_date->format('Y-m-d'))->toBe($course->start_at->format('Y-m-d'));
});

test('enrollment fails with a course that does not belong to the offer', function () {
    $offer = Offer::factory()->create();
    $otherOffer = Offer::factory()->create();
    $course = Course::factory()->for($otherOffer)->create();

    $this->post(route('enrollment.store', $offer), validEnrollmentData($course->id))
        ->assertSessionHasErrors('course_id');
});

test('enrollment fails with a past course date', function () {
    $offer = Offer::factory()->create();
    $course = Course::factory()->past()->for($offer)->create();

    $this->post(route('enrollment.store', $offer), validEnrollmentData($course->id))
        ->assertSessionHasErrors('course_id');
});

test('enrollment show page passes course events for the offer', function () {
    $offer = Offer::factory()->create();
    $startAt = now()->addWeek();
    Course::factory()->for($offer)->create([
        'start_at' => $startAt,
        'end_at' => $startAt->copy()->addHours(8),
    ]);
    Course::factory()->past()->for($offer)->create();

    $this->get(route('enrollment.show', $offer))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('courseEvents', 1)
            ->where('courseEvents.0.start', $startAt->format('Y-m-d H:i'))
        );
});
