<?php

use App\Models\Booking;
use App\Models\Enrollment;
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

test('admin receives day counts and enrollments props', function () {
    $admin = User::factory()->create();
    Booking::factory()->create();
    Enrollment::factory()->create();

    actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->has('dayCounts', 1)
            ->has('dayCounts.0.date')
            ->has('dayCounts.0.count')
            ->has('enrollments', 1)
        );
});

test('instructor receives day counts prop with empty enrollments', function () {
    $instructor = User::factory()->instructor()->create();
    Booking::factory()->create();

    actingAs($instructor)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->has('dayCounts', 1)
            ->has('enrollments', 0)
        );
});

test('team bookings on same slot count as one in day counts', function () {
    $admin = User::factory()->create();
    $team = Team::factory()->create();
    $startsAt = now()->addDay()->setHour(10)->setMinute(0)->setSecond(0);

    // Two bookings same team, same slot
    Booking::factory()->count(2)->create([
        'team_id' => $team->id,
        'starts_at' => $startsAt,
        'ends_at' => $startsAt->copy()->addHour(),
    ]);

    actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('dayCounts', 1)
            ->where('dayCounts.0.count', 1)
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
