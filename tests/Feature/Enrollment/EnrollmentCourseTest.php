<?php

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Offer;
use App\Models\Student;

test('enrollment belongs to a course', function () {
    $offer = Offer::factory()->create();
    $course = Course::factory()->for($offer)->create();
    $student = Student::factory()->create();

    $enrollment = Enrollment::factory()->create([
        'offer_id' => $offer->id,
        'course_id' => $course->id,
        'student_id' => $student->id,
    ]);

    expect($enrollment->course)->toBeInstanceOf(Course::class);
    expect($enrollment->course->id)->toBe($course->id);
});

test('enrollment course_id can be null', function () {
    $student = Student::factory()->create();
    $offer = Offer::factory()->create();

    $enrollment = Enrollment::factory()->create([
        'offer_id' => $offer->id,
        'student_id' => $student->id,
        'course_id' => null,
    ]);

    expect($enrollment->course)->toBeNull();
});
