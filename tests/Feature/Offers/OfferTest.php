<?php

use App\Models\Offer;
use App\Models\Student;
use App\Models\User;

test('admin can view offers index', function () {
    $admin = User::factory()->create();
    Offer::factory()->count(3)->create();

    $this->actingAs($admin)
        ->get(route('offers.index'))
        ->assertOk();
});

test('instructor can view offers index', function () {
    $instructor = User::factory()->instructor()->create();

    $this->actingAs($instructor)
        ->get(route('offers.index'))
        ->assertOk();
});

test('student cannot view offers index', function () {
    $student = Student::factory()->create();

    $this->actingAs($student->user)
        ->get(route('offers.index'))
        ->assertForbidden();
});

test('admin can create an offer', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('offers.store'), [
            'name' => 'Pakke A',
            'price' => '15000.00',
            'type' => 'primary',
            'theory_lessons' => 29,
            'driving_lessons' => 25,
            'track_required' => true,
            'slippery_required' => true,
        ])
        ->assertRedirect(route('offers.index'));

    expect(Offer::count())->toBe(1);
    expect(Offer::first()->name)->toBe('Pakke A');
});

test('admin can update an offer', function () {
    $admin = User::factory()->create();
    $offer = Offer::factory()->create();

    $this->actingAs($admin)
        ->put(route('offers.update', $offer), [
            'name' => 'Opdateret Pakke',
            'price' => '18000.00',
            'type' => 'primary',
            'theory_lessons' => 29,
            'driving_lessons' => 25,
            'track_required' => true,
            'slippery_required' => true,
        ])
        ->assertRedirect(route('offers.index'));

    expect($offer->fresh()->name)->toBe('Opdateret Pakke');
    expect($offer->fresh()->price)->toBe('18000.00');
});

test('admin can delete an offer', function () {
    $admin = User::factory()->create();
    $offer = Offer::factory()->create();

    $this->actingAs($admin)
        ->delete(route('offers.destroy', $offer))
        ->assertRedirect(route('offers.index'));

    expect(Offer::find($offer->id))->toBeNull();
});

test('instructor cannot create an offer', function () {
    $instructor = User::factory()->instructor()->create();

    $this->actingAs($instructor)
        ->post(route('offers.store'), [
            'name' => 'Pakke B',
            'price' => '12000.00',
            'type' => 'primary',
            'theory_lessons' => 29,
            'driving_lessons' => 25,
        ])
        ->assertForbidden();
});

test('offer type must be valid', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('offers.store'), [
            'name' => 'Pakke C',
            'price' => '10000.00',
            'type' => 'invalid_type',
            'theory_lessons' => 20,
            'driving_lessons' => 15,
        ])
        ->assertSessionHasErrors('type');
});
