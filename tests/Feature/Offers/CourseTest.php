<?php

use App\Models\Course;
use App\Models\Offer;
use App\Models\User;

test('admin can add a course date to an offer', function () {
    $admin = User::factory()->create();
    $offer = Offer::factory()->create();

    $this->actingAs($admin)
        ->from(route('offers.edit', $offer))
        ->post(route('offers.courses.store', $offer), [
            'start_at' => '2026-06-01 09:00:00',
            'end_at' => '2026-06-01 17:00:00',
        ])
        ->assertRedirect(route('offers.edit', $offer));

    expect(Course::count())->toBe(1);
    expect(Course::first()->start_at->format('Y-m-d'))->toBe('2026-06-01');
    expect(Course::first()->offer_id)->toBe($offer->id);
});

test('admin can delete a course date', function () {
    $admin = User::factory()->create();
    $offer = Offer::factory()->create();
    $course = Course::factory()->for($offer)->create();

    $this->actingAs($admin)
        ->delete(route('offers.courses.destroy', [$offer, $course]))
        ->assertRedirect(route('offers.edit', $offer));

    expect(Course::find($course->id))->toBeNull();
});

test('instructor cannot add a course date', function () {
    $instructor = User::factory()->instructor()->create();
    $offer = Offer::factory()->create();

    $this->actingAs($instructor)
        ->post(route('offers.courses.store', $offer), [
            'start_at' => '2026-06-01 09:00:00',
            'end_at' => '2026-06-01 17:00:00',
        ])
        ->assertForbidden();
});

test('course start_at must be in the future', function () {
    $admin = User::factory()->create();
    $offer = Offer::factory()->create();

    $this->actingAs($admin)
        ->post(route('offers.courses.store', $offer), [
            'start_at' => '2020-01-01 09:00:00',
            'end_at' => '2020-01-01 17:00:00',
        ])
        ->assertSessionHasErrors('start_at');
});
