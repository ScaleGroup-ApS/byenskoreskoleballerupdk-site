<?php

use App\Events\StudentEnrolled;
use App\Models\Student;
use App\Models\User;
use Thunk\Verbs\Facades\Verbs;

test('admin can view students index', function () {
    $admin = User::factory()->create();
    Student::factory()->count(3)->create();

    $this->actingAs($admin)
        ->get(route('students.index'))
        ->assertOk();
});

test('instructor can view students index', function () {
    $instructor = User::factory()->instructor()->create();

    $this->actingAs($instructor)
        ->get(route('students.index'))
        ->assertOk();
});

test('student cannot view students index', function () {
    $student = Student::factory()->create();

    $this->actingAs($student->user)
        ->get(route('students.index'))
        ->assertForbidden();
});

test('admin can create a student', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('students.store'), [
            'name' => 'Jonas Hansen',
            'email' => 'jonas@example.com',
            'phone' => '+4512345678',
            'cpr' => '010190-1234',
            'start_date' => '2026-03-01',
        ])
        ->assertRedirect();

    expect(Student::count())->toBe(1);
});

test('admin can update a student', function () {
    $admin = User::factory()->create();
    $student = Student::factory()->create();

    $this->actingAs($admin)
        ->put(route('students.update', $student), [
            'name' => 'Updated Name',
            'email' => $student->user->email,
        ])
        ->assertRedirect();

    expect($student->fresh()->user->name)->toBe('Updated Name');
});

test('admin can delete a student', function () {
    $admin = User::factory()->create();
    $student = Student::factory()->create();

    $this->actingAs($admin)
        ->delete(route('students.destroy', $student))
        ->assertRedirect(route('students.index'));

    expect(Student::find($student->id))->toBeNull();
});

test('instructor cannot create a student', function () {
    $instructor = User::factory()->instructor()->create();

    $this->actingAs($instructor)
        ->post(route('students.store'), [
            'name' => 'Jonas Hansen',
            'email' => 'jonas@example.com',
        ])
        ->assertForbidden();
});

test('student can view own profile', function () {
    $student = Student::factory()->create();

    $this->actingAs($student->user)
        ->get(route('students.show', $student))
        ->assertOk();
});

test('student cannot view other student profile', function () {
    $student1 = Student::factory()->create();
    $student2 = Student::factory()->create();

    $this->actingAs($student1->user)
        ->get(route('students.show', $student2))
        ->assertForbidden();
});

test('admin sees eventTimeline prop on student show page', function () {
    $admin = User::factory()->create();
    $student = Student::factory()->create();

    StudentEnrolled::fire(
        student_id: $student->id,
        student_name: $student->user->name,
        start_date: $student->start_date->toDateString(),
    );

    Verbs::commit();

    $this->actingAs($admin)
        ->get(route('students.show', $student))
        ->assertInertia(fn ($page) => $page
            ->has('eventTimeline', 1)
            ->where('eventTimeline.0.summary', 'Elev tilmeldt')
            ->where('eventTimeline.0.category', 'student')
            ->has('eventTimeline.0.id')
            ->has('eventTimeline.0.created_at')
        );
});

test('instructor sees empty eventTimeline on student show page', function () {
    $instructor = User::factory()->instructor()->create();
    $student = Student::factory()->create();

    StudentEnrolled::fire(
        student_id: $student->id,
        student_name: $student->user->name,
        start_date: $student->start_date->toDateString(),
    );

    Verbs::commit();

    $this->actingAs($instructor)
        ->get(route('students.show', $student))
        ->assertInertia(fn ($page) => $page
            ->has('eventTimeline', 0)
        );
});

test('student sees empty eventTimeline on own profile', function () {
    $student = Student::factory()->create();

    StudentEnrolled::fire(
        student_id: $student->id,
        student_name: $student->user->name,
        start_date: $student->start_date->toDateString(),
    );

    Verbs::commit();

    $this->actingAs($student->user)
        ->get(route('students.show', $student))
        ->assertInertia(fn ($page) => $page
            ->has('eventTimeline', 0)
        );
});
