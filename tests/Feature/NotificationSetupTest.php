<?php

use App\Models\User;

test('notifications are shared via Inertia', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('auth.notifications')
            ->has('auth.unread_count')
        );
});

test('unread count is zero with no notifications', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('auth.unread_count', 0)
        );
});
