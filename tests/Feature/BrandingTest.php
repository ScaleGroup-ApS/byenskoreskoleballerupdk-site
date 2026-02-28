<?php

use App\Models\User;

test('branding config is shared via Inertia', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('branding'));
});

test('branding uses custom name when configured', function () {
    config(['branding.name' => 'My Driving School']);

    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('branding.name', 'My Driving School')
        );
});
