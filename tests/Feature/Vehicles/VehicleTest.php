<?php

use App\Models\Student;
use App\Models\User;
use App\Models\Vehicle;

test('admin can view vehicles index', function () {
    $admin = User::factory()->create();
    Vehicle::factory()->count(3)->create();

    $this->actingAs($admin)
        ->get(route('vehicles.index'))
        ->assertOk();
});

test('instructor can view vehicles index', function () {
    $instructor = User::factory()->instructor()->create();

    $this->actingAs($instructor)
        ->get(route('vehicles.index'))
        ->assertOk();
});

test('student cannot view vehicles index', function () {
    $student = Student::factory()->create();

    $this->actingAs($student->user)
        ->get(route('vehicles.index'))
        ->assertForbidden();
});

test('admin can create a vehicle', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('vehicles.store'), [
            'name' => 'Toyota Yaris #1',
            'plate_number' => 'AB12345',
        ])
        ->assertRedirect(route('vehicles.index'));

    expect(Vehicle::count())->toBe(1);
    expect(Vehicle::first()->plate_number)->toBe('AB12345');
});

test('admin can update a vehicle', function () {
    $admin = User::factory()->create();
    $vehicle = Vehicle::factory()->create();

    $this->actingAs($admin)
        ->put(route('vehicles.update', $vehicle), [
            'name' => 'Updated Car',
            'plate_number' => $vehicle->plate_number,
            'active' => false,
        ])
        ->assertRedirect(route('vehicles.index'));

    expect($vehicle->fresh()->name)->toBe('Updated Car');
    expect($vehicle->fresh()->active)->toBeFalse();
});

test('admin can delete a vehicle', function () {
    $admin = User::factory()->create();
    $vehicle = Vehicle::factory()->create();

    $this->actingAs($admin)
        ->delete(route('vehicles.destroy', $vehicle))
        ->assertRedirect(route('vehicles.index'));

    expect(Vehicle::find($vehicle->id))->toBeNull();
});

test('instructor cannot create a vehicle', function () {
    $instructor = User::factory()->instructor()->create();

    $this->actingAs($instructor)
        ->post(route('vehicles.store'), [
            'name' => 'Car',
            'plate_number' => 'XX99999',
        ])
        ->assertForbidden();
});

test('plate number must be unique', function () {
    $admin = User::factory()->create();
    Vehicle::factory()->create(['plate_number' => 'AB12345']);

    $this->actingAs($admin)
        ->post(route('vehicles.store'), [
            'name' => 'Another Car',
            'plate_number' => 'AB12345',
        ])
        ->assertSessionHasErrors('plate_number');
});
