<?php

use App\Models\Booking;
use App\Models\Team;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('guests are redirected to login', function () {
    get('/bookings/day/2026-02-24')->assertRedirect(route('login'));
});

test('admin can view a day page', function () {
    $admin = User::factory()->create();

    actingAs($admin)
        ->get('/bookings/day/2026-02-24')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('bookings/day'));
});

test('individual bookings appear as separate events', function () {
    $admin = User::factory()->create();
    $date = now()->addDay()->toDateString();

    Booking::factory()->count(2)->create([
        'starts_at' => $date.' 10:00:00',
        'ends_at' => $date.' 10:45:00',
    ]);

    actingAs($admin)
        ->get("/bookings/day/{$date}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('events', 2));
});

test('team bookings on same slot are grouped into one event', function () {
    $admin = User::factory()->create();
    $team = Team::factory()->create(['name' => 'Hold A']);
    $date = now()->addDay()->toDateString();

    Booking::factory()->count(3)->create([
        'team_id' => $team->id,
        'starts_at' => $date.' 09:00:00',
        'ends_at' => $date.' 10:30:00',
    ]);

    actingAs($admin)
        ->get("/bookings/day/{$date}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('events', 1)
            ->where('events.0.title', 'Hold A')
            ->where('events.0.team_id', $team->id)
        );
});

test('mixed individual and team bookings returned correctly', function () {
    $admin = User::factory()->create();
    $team = Team::factory()->create();
    $date = now()->addDay()->toDateString();

    Booking::factory()->create(['starts_at' => $date.' 08:00:00', 'ends_at' => $date.' 08:45:00']);
    Booking::factory()->count(2)->create([
        'team_id' => $team->id,
        'starts_at' => $date.' 09:00:00',
        'ends_at' => $date.' 10:30:00',
    ]);

    actingAs($admin)
        ->get("/bookings/day/{$date}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('events', 2));
});
