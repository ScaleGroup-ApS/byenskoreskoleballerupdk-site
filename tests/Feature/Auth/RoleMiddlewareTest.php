<?php

use App\Models\User;

test('admin can access admin routes', function () {
    $admin = User::factory()->create();
    $this->actingAs($admin)->get(route('dashboard'))->assertOk();
});

test('unauthenticated user is redirected to login', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});
