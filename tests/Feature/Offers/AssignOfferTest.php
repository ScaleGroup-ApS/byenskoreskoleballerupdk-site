<?php

use App\Actions\Offers\AssignOffer;
use App\Models\Offer;
use App\Models\Student;
use App\States\StudentBalanceState;
use Thunk\Verbs\Facades\Verbs;

test('assigning an offer attaches it to the student', function () {
    $student = Student::factory()->create();
    $offer = Offer::factory()->create(['price' => '15000.00']);

    (new AssignOffer)->handle($student, $offer);

    expect($student->offers()->count())->toBe(1);
    expect($student->offers()->first()->id)->toBe($offer->id);
});

test('assigning an offer fires OfferAssigned event and updates balance state', function () {
    $student = Student::factory()->create();
    $offer = Offer::factory()->create(['price' => '15000.00']);

    (new AssignOffer)->handle($student, $offer);

    Verbs::commit();

    $state = StudentBalanceState::load($student->id);

    expect($state->total_owed)->toBe(15000.0);
});

test('assigning multiple offers accumulates total owed', function () {
    $student = Student::factory()->create();
    $offer1 = Offer::factory()->create(['price' => '15000.00']);
    $offer2 = Offer::factory()->addon()->create(['price' => '2500.00']);

    (new AssignOffer)->handle($student, $offer1);
    (new AssignOffer)->handle($student, $offer2);

    Verbs::commit();

    $state = StudentBalanceState::load($student->id);

    expect($state->total_owed)->toBe(17500.0);
    expect($student->offers()->count())->toBe(2);
});

test('assigning the same offer twice does not duplicate the pivot row', function () {
    $student = Student::factory()->create();
    $offer = Offer::factory()->create(['price' => '15000.00']);

    (new AssignOffer)->handle($student, $offer);
    (new AssignOffer)->handle($student, $offer);

    expect($student->offers()->count())->toBe(1);
});
