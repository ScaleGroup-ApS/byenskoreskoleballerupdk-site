<?php

use App\Models\Student;
use App\Models\User;

test('admin can view all students', function () {
    $admin = User::factory()->create();
    expect($admin->can('viewAny', Student::class))->toBeTrue();
});

test('instructor can view all students', function () {
    $instructor = User::factory()->instructor()->create();
    expect($instructor->can('viewAny', Student::class))->toBeTrue();
});

test('student cannot view all students', function () {
    $student = Student::factory()->create();
    expect($student->user->can('viewAny', Student::class))->toBeFalse();
});

test('student can view own profile', function () {
    $student = Student::factory()->create();
    expect($student->user->can('view', $student))->toBeTrue();
});

test('student cannot view other student profile', function () {
    $student1 = Student::factory()->create();
    $student2 = Student::factory()->create();
    expect($student1->user->can('view', $student2))->toBeFalse();
});

test('only admin can create students', function () {
    $admin = User::factory()->create();
    $instructor = User::factory()->instructor()->create();

    expect($admin->can('create', Student::class))->toBeTrue();
    expect($instructor->can('create', Student::class))->toBeFalse();
});

test('only admin can update students', function () {
    $admin = User::factory()->create();
    $student = Student::factory()->create();

    expect($admin->can('update', $student))->toBeTrue();
    expect($student->user->can('update', $student))->toBeFalse();
});

test('only admin can delete students', function () {
    $admin = User::factory()->create();
    $student = Student::factory()->create();

    expect($admin->can('delete', $student))->toBeTrue();
});
