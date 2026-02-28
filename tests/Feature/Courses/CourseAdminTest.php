<?php

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Offer;
use App\Models\User;

test('admin can view upcoming courses', function () {
    $admin = User::factory()->create();
    Course::factory()->create();
    Course::factory()->past()->create();

    $this->actingAs($admin)
        ->get(route('courses.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('courses/index')
            ->has('courses', 1)
        );
});

test('admin can view course detail', function () {
    $admin = User::factory()->create();
    $course = Course::factory()->create();

    $this->actingAs($admin)
        ->get(route('courses.show', $course))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('courses/show')
            ->has('course')
        );
});

test('admin can update a course', function () {
    $admin = User::factory()->create();
    $course = Course::factory()->create();
    $startAt = now()->addWeeks(3);

    $this->actingAs($admin)
        ->patch(route('courses.update', $course), [
            'start_at' => $startAt->format('Y-m-d H:i:s'),
            'end_at' => $startAt->copy()->addHours(8)->format('Y-m-d H:i:s'),
            'max_students' => 15,
        ])
        ->assertRedirect(route('courses.show', $course));

    expect($course->fresh()->max_students)->toBe(15);
});

test('instructor cannot update a course', function () {
    $instructor = User::factory()->instructor()->create();
    $course = Course::factory()->create();
    $startAt = now()->addWeeks(3);

    $this->actingAs($instructor)
        ->patch(route('courses.update', $course), [
            'start_at' => $startAt->format('Y-m-d H:i:s'),
            'end_at' => $startAt->copy()->addHours(8)->format('Y-m-d H:i:s'),
        ])
        ->assertForbidden();
});

test('course detail includes enrolled students', function () {
    $admin = User::factory()->create();
    $offer = Offer::factory()->create();
    $course = Course::factory()->for($offer)->create();
    Enrollment::factory()->create(['course_id' => $course->id, 'offer_id' => $offer->id]);

    $this->actingAs($admin)
        ->get(route('courses.show', $course))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('course.enrollments', 1)
        );
});
