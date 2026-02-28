<?php

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;

test('guest cannot list conversations', function () {
    $this->get(route('chat.index'))->assertRedirect(route('login'));
});

test('user can list their conversations', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create();
    $conversation->users()->attach($user->id);

    $this->actingAs($user)
        ->get(route('chat.index'))
        ->assertOk();
});

test('creating a direct conversation redirects to chat', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('chat.store'), ['user_id' => $other->id]);

    $response->assertRedirect();

    $this->assertDatabaseHas('conversations', ['type' => 'direct']);
});

test('creating a second DM to same user reuses existing conversation', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $existing = Conversation::factory()->create(['type' => 'direct']);
    $existing->users()->attach([$user->id, $other->id]);

    $this->actingAs($user)
        ->post(route('chat.store'), ['user_id' => $other->id])
        ->assertRedirect();

    expect(Conversation::count())->toBe(1);
});

test('participant can list messages', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create();
    $conversation->users()->attach($user->id);

    Message::factory()->create([
        'conversation_id' => $conversation->id,
        'user_id' => $user->id,
        'body' => 'Hej!',
    ]);

    $this->actingAs($user)
        ->getJson(route('chat.messages.index', $conversation))
        ->assertOk()
        ->assertJsonPath('data.0.body', 'Hej!');
});

test('non-participant cannot list messages', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create();

    $this->actingAs($user)
        ->getJson(route('chat.messages.index', $conversation))
        ->assertForbidden();
});

test('participant can send a message', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create();
    $conversation->users()->attach($user->id);

    $this->actingAs($user)
        ->postJson(route('chat.messages.store', $conversation), ['body' => 'God morgen!'])
        ->assertCreated()
        ->assertJsonPath('body', 'God morgen!');

    $this->assertDatabaseHas('messages', ['body' => 'God morgen!', 'conversation_id' => $conversation->id]);
});

test('non-participant cannot send a message', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create();

    $this->actingAs($user)
        ->postJson(route('chat.messages.store', $conversation), ['body' => 'Hej'])
        ->assertForbidden();
});

test('non-participant is denied SSE stream access', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create();

    $this->actingAs($user)
        ->get(route('chat.messages.stream', $conversation))
        ->assertForbidden();
});
