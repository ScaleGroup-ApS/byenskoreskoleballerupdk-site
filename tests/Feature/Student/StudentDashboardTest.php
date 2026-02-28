<?php

use App\Models\Booking;
use App\Models\Student;
use App\Models\User;

test('student visiting /dashboard is redirected to student dashboard', function () {
    $user = User::factory()->student()->create();
    Student::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('student.dashboard'));
});

test('student can visit their dashboard', function () {
    $user = User::factory()->student()->create();
    Student::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('student.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('student/index')
            ->has('booking')
            ->has('readiness')
            ->has('balance')
            ->has('materials')
        );
});

test('admin cannot visit student dashboard', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('student.dashboard'))
        ->assertForbidden();
});

test('instructor cannot visit student dashboard', function () {
    $instructor = User::factory()->instructor()->create();

    $this->actingAs($instructor)
        ->get(route('student.dashboard'))
        ->assertForbidden();
});

test('student dashboard shows next upcoming booking', function () {
    $user = User::factory()->student()->create();
    $student = Student::factory()->for($user)->create();
    Booking::factory()->for($student)->create([
        'starts_at' => now()->addDays(2),
        'ends_at' => now()->addDays(2)->addHours(1),
    ]);

    $this->actingAs($user)
        ->get(route('student.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->whereNot('booking', null)
        );
});

test('student dashboard booking is null when no upcoming bookings', function () {
    $user = User::factory()->student()->create();
    Student::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('student.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('booking', null)
        );
});
