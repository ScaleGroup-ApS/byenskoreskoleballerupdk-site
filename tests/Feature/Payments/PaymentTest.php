<?php

use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use App\States\StudentBalanceState;
use Thunk\Verbs\Facades\Verbs;

test('admin can view payments index', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('payments.index'))
        ->assertOk();
});

test('instructor can view payments index', function () {
    $instructor = User::factory()->instructor()->create();

    $this->actingAs($instructor)
        ->get(route('payments.index'))
        ->assertOk();
});

test('student cannot view payments index', function () {
    $student = Student::factory()->create();

    $this->actingAs($student->user)
        ->get(route('payments.index'))
        ->assertForbidden();
});

test('admin can record a payment', function () {
    $admin = User::factory()->create();
    $student = Student::factory()->create();

    $this->actingAs($admin)
        ->post(route('payments.store'), [
            'student_id' => $student->id,
            'amount' => '5000.00',
            'method' => 'card',
        ])
        ->assertRedirect(route('payments.index'));

    expect(Payment::count())->toBe(1);
    expect(Payment::first()->amount)->toBe('5000.00');
});

test('recording a payment updates balance state', function () {
    $admin = User::factory()->create();
    $student = Student::factory()->create();

    $this->actingAs($admin)
        ->post(route('payments.store'), [
            'student_id' => $student->id,
            'amount' => '5000.00',
            'method' => 'card',
        ]);

    Verbs::commit();

    $state = StudentBalanceState::load($student->id);

    expect($state->total_paid)->toBe(5000.0);
});

test('instructor cannot record a payment', function () {
    $instructor = User::factory()->instructor()->create();
    $student = Student::factory()->create();

    $this->actingAs($instructor)
        ->post(route('payments.store'), [
            'student_id' => $student->id,
            'amount' => '5000.00',
            'method' => 'card',
        ])
        ->assertForbidden();
});

test('admin can delete a payment', function () {
    $admin = User::factory()->create();
    $payment = Payment::factory()->create();

    $this->actingAs($admin)
        ->delete(route('payments.destroy', $payment))
        ->assertRedirect(route('payments.index'));

    expect(Payment::find($payment->id))->toBeNull();
});
