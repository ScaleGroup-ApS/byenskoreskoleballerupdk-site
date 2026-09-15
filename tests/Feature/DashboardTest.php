<?php

use App\Models\Booking;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Offer;
use App\Models\Team;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('guests are redirected to the login page', function () {
    get(route('dashboard'))->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();

    actingAs($user)->get(route('dashboard'))->assertOk();
});

test('admin receives courses and enrollments props', function () {
    $admin = User::factory()->create();
    Course::factory()->create();
    Enrollment::factory()->create();

    actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->has('courses', 1)
            ->has('courses.0.id')
            ->has('courses.0.title')
            ->has('courses.0.start')
            ->has('courses.0.end')
            ->has('enrollments', 1)
        );
});

test('instructor receives courses prop with empty enrollments', function () {
    $instructor = User::factory()->instructor()->create();
    Course::factory()->create();

    actingAs($instructor)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->has('courses', 1)
            ->has('enrollments', 0)
        );
});

test('course events use their offer name and schedule', function () {
    $admin = User::factory()->create();
    $offer = Offer::factory()->create(['name' => 'Teorihold']);
    $startsAt = now()->addWeek()->setTime(10, 0);
    $course = Course::factory()->for($offer)->create([
        'start_at' => $startsAt,
        'end_at' => $startsAt->copy()->addHours(2),
    ]);

    actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('courses', 1)
            ->where('courses.0.id', $course->id)
            ->where('courses.0.title', 'Teorihold')
            ->where('courses.0.start', $course->start_at->toIso8601String())
            ->where('courses.0.end', $course->end_at->toIso8601String())
        );
});

test('student visiting dashboard is redirected to student dashboard', function () {
    $student = User::factory()->student()->create();

    actingAs($student)
        ->get(route('dashboard'))
        ->assertRedirect(route('student.dashboard'));
});

test('booking can belong to a team', function () {
    $team = Team::factory()->create();
    $booking = Booking::factory()->create(['team_id' => $team->id]);

    expect($booking->fresh()->team)->not->toBeNull();
    expect($booking->fresh()->team->id)->toBe($team->id);
});
