<?php

use App\Enums\UserRole;
use App\Models\User;

test('user has admin role by default', function () {
    $user = User::factory()->create();
    expect($user->role)->toBe(UserRole::Admin);
    expect($user->isAdmin())->toBeTrue();
});

test('user can be created as instructor', function () {
    $user = User::factory()->instructor()->create();
    expect($user->role)->toBe(UserRole::Instructor);
    expect($user->isInstructor())->toBeTrue();
});

test('user can be created as student', function () {
    $user = User::factory()->student()->create();
    expect($user->role)->toBe(UserRole::Student);
    expect($user->isStudent())->toBeTrue();
});
