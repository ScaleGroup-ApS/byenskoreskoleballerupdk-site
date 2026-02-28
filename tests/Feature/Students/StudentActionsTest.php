<?php

use App\Actions\Students\CreateStudent;
use App\Actions\Students\DeleteStudent;
use App\Actions\Students\SendStudentLoginLink;
use App\Actions\Students\UpdateStudent;
use App\Enums\StudentStatus;
use App\Enums\UserRole;
use App\Models\Student;
use Illuminate\Support\Facades\Mail;

test('CreateStudent creates user and student in transaction', function () {
    Mail::fake();

    $action = new CreateStudent;

    $student = $action->handle([
        'name' => 'Jonas Hansen',
        'email' => 'jonas@example.com',
        'phone' => '+4512345678',
        'cpr' => '010190-1234',
        'start_date' => '2026-03-01',
    ], app(SendStudentLoginLink::class));

    expect($student)->toBeInstanceOf(Student::class);
    expect($student->user->name)->toBe('Jonas Hansen');
    expect($student->user->email)->toBe('jonas@example.com');
    expect($student->user->role)->toBe(UserRole::Student);
    expect($student->phone)->toBe('+4512345678');
    expect($student->cpr)->toBe('010190-1234');
    expect($student->status)->toBe(StudentStatus::Active);
});

test('UpdateStudent updates student and user data', function () {
    $student = Student::factory()->create();
    $action = new UpdateStudent;

    $updated = $action->handle($student, [
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
        'phone' => '+4587654321',
    ]);

    expect($updated->user->name)->toBe('Updated Name');
    expect($updated->user->email)->toBe('updated@example.com');
    expect($updated->phone)->toBe('+4587654321');
});

test('DeleteStudent soft deletes student', function () {
    $student = Student::factory()->create();
    $action = new DeleteStudent;

    $action->handle($student);

    expect(Student::find($student->id))->toBeNull();
    expect(Student::withTrashed()->find($student->id))->not->toBeNull();
});
