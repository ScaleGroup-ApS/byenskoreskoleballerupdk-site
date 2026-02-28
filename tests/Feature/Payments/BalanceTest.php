<?php

use App\Actions\Offers\AssignOffer;
use App\Actions\Payments\CalculateBalance;
use App\Actions\Payments\RecordPayment;
use App\Models\Offer;
use App\Models\Student;
use Thunk\Verbs\Facades\Verbs;

test('single offer with full payment results in zero outstanding', function () {
    $student = Student::factory()->create();
    $offer = Offer::factory()->create(['price' => '15000.00']);

    (new AssignOffer)->handle($student, $offer);
    (new RecordPayment)->handle([
        'student_id' => $student->id,
        'amount' => '15000.00',
        'method' => 'card',
    ]);

    Verbs::commit();

    $balance = (new CalculateBalance)->handle($student);

    expect($balance['total_owed'])->toBe(15000.0);
    expect($balance['total_paid'])->toBe(15000.0);
    expect($balance['outstanding'])->toBe(0.0);
});

test('multiple offers with partial payment shows correct outstanding', function () {
    $student = Student::factory()->create();
    $primary = Offer::factory()->create(['price' => '15000.00']);
    $addon = Offer::factory()->addon()->create(['price' => '2500.00']);

    (new AssignOffer)->handle($student, $primary);
    (new AssignOffer)->handle($student, $addon);
    (new RecordPayment)->handle([
        'student_id' => $student->id,
        'amount' => '5000.00',
        'method' => 'card',
    ]);

    Verbs::commit();

    $balance = (new CalculateBalance)->handle($student);

    expect($balance['total_owed'])->toBe(17500.0);
    expect($balance['total_paid'])->toBe(5000.0);
    expect($balance['outstanding'])->toBe(12500.0);
});

test('student with no offers or payments has zero balance', function () {
    $student = Student::factory()->create();

    Verbs::commit();

    $balance = (new CalculateBalance)->handle($student);

    expect($balance['total_owed'])->toBe(0.0);
    expect($balance['total_paid'])->toBe(0.0);
    expect($balance['outstanding'])->toBe(0.0);
});

test('addon offer is included in total owed', function () {
    $student = Student::factory()->create();
    $addon = Offer::factory()->addon()->create(['price' => '2500.00']);

    (new AssignOffer)->handle($student, $addon);

    Verbs::commit();

    $balance = (new CalculateBalance)->handle($student);

    expect($balance['total_owed'])->toBe(2500.0);
    expect($balance['outstanding'])->toBe(2500.0);
});
