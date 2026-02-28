<?php

use App\Enums\StudentStatus;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;

test('student belongs to a user with student role', function () {
    $student = Student::factory()->create();

    expect($student->user)->toBeInstanceOf(User::class);
    expect($student->user->isStudent())->toBeTrue();
});

test('cpr is encrypted in database and decrypted on access', function () {
    $student = Student::factory()->create(['cpr' => '010190-1234']);

    $raw = DB::table('students')->where('id', $student->id)->value('cpr');
    expect($raw)->not->toBe('010190-1234');

    $student->refresh();
    expect($student->cpr)->toBe('010190-1234');
});

test('student status is cast to enum', function () {
    $student = Student::factory()->create();
    expect($student->status)->toBeInstanceOf(StudentStatus::class);
    expect($student->status)->toBe(StudentStatus::Active);
});

test('student can be soft deleted', function () {
    $student = Student::factory()->create();
    $student->delete();

    expect(Student::find($student->id))->toBeNull();
    expect(Student::withTrashed()->find($student->id))->not->toBeNull();
});

test('user has student relation', function () {
    $student = Student::factory()->create();
    $user = $student->user;

    expect($user->student)->toBeInstanceOf(Student::class);
    expect($user->student->id)->toBe($student->id);
});
