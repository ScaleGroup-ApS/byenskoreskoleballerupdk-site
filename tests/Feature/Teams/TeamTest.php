<?php

use App\Models\Student;
use App\Models\Team;
use App\Models\User;

test('admin can view teams index', function () {
    $admin = User::factory()->create();
    Team::factory()->count(2)->create();

    $this->actingAs($admin)
        ->get(route('teams.index'))
        ->assertOk();
});

test('instructor can view teams index', function () {
    $instructor = User::factory()->instructor()->create();

    $this->actingAs($instructor)
        ->get(route('teams.index'))
        ->assertOk();
});

test('student cannot view teams index', function () {
    $student = Student::factory()->create();

    $this->actingAs($student->user)
        ->get(route('teams.index'))
        ->assertForbidden();
});

test('admin can create a team', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('teams.store'), [
            'name' => 'Hold A',
            'description' => 'Første hold',
        ])
        ->assertRedirect();

    expect(Team::count())->toBe(1);
    expect(Team::first()->name)->toBe('Hold A');
});

test('admin can update a team', function () {
    $admin = User::factory()->create();
    $team = Team::factory()->create();

    $this->actingAs($admin)
        ->put(route('teams.update', $team), [
            'name' => 'Updated Team',
        ])
        ->assertRedirect();

    expect($team->fresh()->name)->toBe('Updated Team');
});

test('admin can delete a team', function () {
    $admin = User::factory()->create();
    $team = Team::factory()->create();

    $this->actingAs($admin)
        ->delete(route('teams.destroy', $team))
        ->assertRedirect(route('teams.index'));

    expect(Team::find($team->id))->toBeNull();
});

test('instructor cannot create a team', function () {
    $instructor = User::factory()->instructor()->create();

    $this->actingAs($instructor)
        ->post(route('teams.store'), [
            'name' => 'Hold B',
        ])
        ->assertForbidden();
});

test('team has many students via pivot', function () {
    $team = Team::factory()->create();
    $students = Student::factory()->count(3)->create();

    $team->students()->attach($students->pluck('id'));

    expect($team->students)->toHaveCount(3);
});

test('student belongs to many teams', function () {
    $student = Student::factory()->create();
    $teams = Team::factory()->count(2)->create();

    $student->teams()->attach($teams->pluck('id'));

    expect($student->teams)->toHaveCount(2);
});
