<?php

use App\Models\Student;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('admin can upload media to student', function () {
    Storage::fake('media');
    $admin = User::factory()->create();
    $student = Student::factory()->create();

    $this->actingAs($admin)
        ->post(route('students.media.store', $student), [
            'file' => UploadedFile::fake()->create('document.pdf', 100),
            'collection' => 'documents',
        ])
        ->assertRedirect();

    expect($student->getMedia('documents'))->toHaveCount(1);
});

test('student cannot upload media to own profile', function () {
    $student = Student::factory()->create();

    $this->actingAs($student->user)
        ->post(route('students.media.store', $student), [
            'file' => UploadedFile::fake()->create('document.pdf', 100),
            'collection' => 'documents',
        ])
        ->assertForbidden();
});

test('admin can delete student media', function () {
    $admin = User::factory()->create();
    $student = Student::factory()->create();

    $media = $student->addMedia(UploadedFile::fake()->create('test.pdf', 100))
        ->toMediaCollection('documents');

    $this->actingAs($admin)
        ->delete(route('students.media.destroy', [$student, $media]))
        ->assertRedirect();

    expect($student->fresh()->getMedia('documents'))->toHaveCount(0);
});

test('student can view own media', function () {
    $student = Student::factory()->create();

    $media = $student->addMedia(UploadedFile::fake()->create('test.pdf', 100))
        ->toMediaCollection('documents');

    $this->actingAs($student->user)
        ->get(route('students.media.show', [$student, $media]))
        ->assertOk();
});

test('student cannot view other student media', function () {
    $student1 = Student::factory()->create();
    $student2 = Student::factory()->create();

    $media = $student2->addMedia(UploadedFile::fake()->create('test.pdf', 100))
        ->toMediaCollection('documents');

    $this->actingAs($student1->user)
        ->get(route('students.media.show', [$student2, $media]))
        ->assertForbidden();
});
