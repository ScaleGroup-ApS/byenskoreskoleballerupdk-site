<?php

use App\Models\Offer;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('student can view material from their assigned offer', function () {
    Storage::fake('media');
    $user = User::factory()->student()->create();
    $student = Student::factory()->for($user)->create();
    $offer = Offer::factory()->create();
    $student->offers()->attach($offer, ['assigned_at' => now()]);

    $media = $offer->addMedia(UploadedFile::fake()->create('lesson-notes.pdf', 100))
        ->toMediaCollection('materials');

    $this->actingAs($user)
        ->get(route('student.offers.materials.show', [$offer, $media]))
        ->assertOk();
});

test('student cannot view material from an offer they are not assigned to', function () {
    Storage::fake('media');
    $user = User::factory()->student()->create();
    Student::factory()->for($user)->create();
    $offer = Offer::factory()->create();

    $media = $offer->addMedia(UploadedFile::fake()->create('lesson-notes.pdf', 100))
        ->toMediaCollection('materials');

    $this->actingAs($user)
        ->get(route('student.offers.materials.show', [$offer, $media]))
        ->assertForbidden();
});

test('student cannot access media from a different offer using mismatched ids', function () {
    Storage::fake('media');
    $user = User::factory()->student()->create();
    $student = Student::factory()->for($user)->create();
    $offer1 = Offer::factory()->create();
    $offer2 = Offer::factory()->create();
    $student->offers()->attach($offer1, ['assigned_at' => now()]);

    $media = $offer2->addMedia(UploadedFile::fake()->create('lesson-notes.pdf', 100))
        ->toMediaCollection('materials');

    $this->actingAs($user)
        ->get(route('student.offers.materials.show', [$offer1, $media]))
        ->assertNotFound();
});

test('student dashboard includes materials from assigned offers', function () {
    Storage::fake('media');
    $user = User::factory()->student()->create();
    $student = Student::factory()->for($user)->create();
    $offer = Offer::factory()->create();
    $student->offers()->attach($offer, ['assigned_at' => now()]);

    $offer->addMedia(UploadedFile::fake()->create('lesson-notes.pdf', 100))
        ->toMediaCollection('materials');

    $this->actingAs($user)
        ->get(route('student.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('materials', 1)
            ->has('materials.0', fn ($material) => $material
                ->has('id')
                ->has('name')
                ->has('file_name')
                ->has('mime_type')
                ->has('size')
                ->has('url')
                ->has('offer_name')
            )
        );
});

test('student dashboard materials is empty when offer has no materials', function () {
    $user = User::factory()->student()->create();
    $student = Student::factory()->for($user)->create();
    $offer = Offer::factory()->create();
    $student->offers()->attach($offer, ['assigned_at' => now()]);

    $this->actingAs($user)
        ->get(route('student.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('materials', [])
        );
});

test('admin cannot access student offer materials route', function () {
    Storage::fake('media');
    $admin = User::factory()->create();
    $offer = Offer::factory()->create();

    $media = $offer->addMedia(UploadedFile::fake()->create('lesson-notes.pdf', 100))
        ->toMediaCollection('materials');

    $this->actingAs($admin)
        ->get(route('student.offers.materials.show', [$offer, $media]))
        ->assertForbidden();
});
