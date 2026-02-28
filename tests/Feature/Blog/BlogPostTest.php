<?php

use App\Enums\UserRole;
use App\Models\BlogPost;
use App\Models\User;

test('admin can view blog index', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    BlogPost::factory()->count(3)->create();

    $this->actingAs($admin)
        ->get(route('blog.index'))
        ->assertOk();
});

test('instructor cannot view blog index', function () {
    $instructor = User::factory()->create(['role' => UserRole::Instructor]);

    $this->actingAs($instructor)
        ->get(route('blog.index'))
        ->assertForbidden();
});

test('admin can create a blog post', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)
        ->post(route('blog.store'), [
            'title' => 'Test indlæg',
            'body' => 'Indhold her.',
            'published' => false,
        ])
        ->assertRedirect(route('blog.index'));

    expect(BlogPost::query()->where('title', 'Test indlæg')->exists())->toBeTrue();
});

test('slug is auto-generated from title on create', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)->post(route('blog.store'), [
        'title' => 'Min Forste Artikel',
        'body' => 'Tekst',
        'published' => false,
    ]);

    $post = BlogPost::query()->first();
    expect($post->slug)->toBe('min-forste-artikel');
});

test('published_at is set automatically when publishing without a date', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)->post(route('blog.store'), [
        'title' => 'Publiceret nu',
        'body' => 'Indhold',
        'published' => true,
    ]);

    $post = BlogPost::query()->first();
    expect($post->published_at)->not->toBeNull();
});

test('admin can update a blog post', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $post = BlogPost::factory()->create();

    $this->actingAs($admin)
        ->patch(route('blog.update', $post), ['title' => 'Opdateret titel', 'body' => 'Ny tekst'])
        ->assertRedirect(route('blog.index'));

    expect($post->fresh()->title)->toBe('Opdateret titel');
});

test('admin can delete a blog post', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $post = BlogPost::factory()->create();

    $this->actingAs($admin)
        ->delete(route('blog.destroy', $post))
        ->assertRedirect(route('blog.index'));

    expect(BlogPost::query()->find($post->id))->toBeNull();
});

test('public can view a published blog post without auth', function () {
    $post = BlogPost::factory()->published()->create();

    $this->get(route('blog.show', $post->slug))
        ->assertOk();
});
