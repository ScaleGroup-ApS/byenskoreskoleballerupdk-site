<?php

use App\Actions\Students\CreateStudent;
use App\Actions\Students\SendStudentLoginLink;
use App\Actions\Students\UpdateStudent;
use App\Models\Student;
use App\States\StudentProgressionState;
use Illuminate\Support\Facades\Mail;
use Thunk\Verbs\Facades\Verbs;

test('creating a student fires StudentEnrolled event', function () {
    Mail::fake();

    $action = new CreateStudent;

    $student = $action->handle([
        'name' => 'Jonas Hansen',
        'email' => 'jonas@example.com',
        'start_date' => '2026-03-01',
    ], app(SendStudentLoginLink::class));

    Verbs::commit();

    $state = StudentProgressionState::load($student->id);

    expect($state->enrolled_at)->toBe('2026-03-01');
    expect($state->lesson_counts)->toBe([]);
});

test('changing student status fires StudentStatusChanged event', function () {
    $student = Student::factory()->create();
    $action = new UpdateStudent;

    $action->handle($student, [
        'status' => 'inactive',
    ]);

    Verbs::commit();

    expect($student->fresh()->status->value)->toBe('inactive');
});

test('updating student without status change does not fire event', function () {
    $student = Student::factory()->create();
    $action = new UpdateStudent;

    $action->handle($student, [
        'name' => 'New Name',
    ]);

    Verbs::commit();

    expect($student->fresh()->user->name)->toBe('New Name');
});
