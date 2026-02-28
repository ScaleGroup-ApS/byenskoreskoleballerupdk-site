<?php

use App\Enums\ConversationType;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;

test('conversation has participants via BelongsToMany', function () {
    $conversation = Conversation::factory()->create();
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $conversation->users()->attach([$user1->id, $user2->id]);

    expect($conversation->users()->count())->toBe(2);
});

test('conversation type is cast to enum', function () {
    $conversation = Conversation::factory()->create(['type' => 'direct']);

    expect($conversation->type)->toBe(ConversationType::Direct);
});

test('group conversation has a name', function () {
    $conversation = Conversation::factory()->group()->create();

    expect($conversation->type)->toBe(ConversationType::Group);
    expect($conversation->name)->not->toBeNull();
});

test('message belongs to conversation and user', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create();
    $message = Message::factory()->create([
        'conversation_id' => $conversation->id,
        'user_id' => $user->id,
    ]);

    expect($message->conversation->id)->toBe($conversation->id);
    expect($message->user->id)->toBe($user->id);
});

test('conversation last message returns latest message', function () {
    $conversation = Conversation::factory()->create();
    $user = User::factory()->create();

    Message::factory()->create(['conversation_id' => $conversation->id, 'user_id' => $user->id, 'body' => 'Første besked']);
    $last = Message::factory()->create(['conversation_id' => $conversation->id, 'user_id' => $user->id, 'body' => 'Seneste besked']);

    expect($conversation->lastMessage->id)->toBe($last->id);
});

test('pivot tracks last_read_at', function () {
    $conversation = Conversation::factory()->create();
    $user = User::factory()->create();

    $readAt = now();
    $conversation->users()->attach($user->id, ['last_read_at' => $readAt]);

    $pivot = $conversation->users()->withPivot('last_read_at')->first()->pivot;
    expect($pivot->last_read_at)->not->toBeNull();
});
