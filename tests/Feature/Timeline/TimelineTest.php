<?php

use App\Models\Student;
use App\Models\User;

use function Pest\Laravel\actingAs;

test('admin can view the timeline', function () {
    $admin = User::factory()->create();

    actingAs($admin)
        ->get(route('timeline.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('timeline/index')->has('events'));
});

test('instructor cannot view the timeline', function () {
    $instructor = User::factory()->instructor()->create();

    actingAs($instructor)
        ->get(route('timeline.index'))
        ->assertForbidden();
});

test('student cannot view the timeline', function () {
    $student = Student::factory()->create();

    actingAs($student->user)
        ->get(route('timeline.index'))
        ->assertForbidden();
});

test('guest is redirected from the timeline', function () {
    $this->get(route('timeline.index'))->assertRedirect(route('login'));
});
