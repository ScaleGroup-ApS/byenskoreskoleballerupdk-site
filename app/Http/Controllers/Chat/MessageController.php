<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\StoreMessageRequest;
use App\Models\Conversation;
use App\Models\Message;
use App\Notifications\NewMessageNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\StreamedResponse;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    public function index(Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $messages = $conversation->messages()
            ->with('user')
            ->paginate(50);

        $conversation->users()->updateExistingPivot(Auth::id(), ['last_read_at' => now()]);

        return response()->json($messages);
    }

    public function store(StoreMessageRequest $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        /** @var \App\Models\User $sender */
        $sender = Auth::user();

        $message = $conversation->messages()->create([
            'user_id' => $sender->id,
            'body' => $request->validated('body'),
        ]);

        $message->load('user');

        $conversation->users()
            ->where('user_id', '!=', $sender->id)
            ->get()
            ->each(fn ($recipient) => $recipient->notify(
                new NewMessageNotification($sender->name, (string) $conversation->id)
            ));

        return response()->json([
            'id' => $message->id,
            'body' => $message->body,
            'user' => ['id' => $message->user->id, 'name' => $message->user->name],
            'created_at' => $message->created_at->toISOString(),
        ], 201);
    }

    public function stream(Conversation $conversation, Request $request): StreamedResponse
    {
        $this->authorize('view', $conversation);

        $after = $request->query('after', now()->subSecond()->toISOString());

        return response()->eventStream(function () use ($conversation, &$after) {
            while (true) {
                $messages = Message::query()
                    ->where('conversation_id', $conversation->id)
                    ->where('created_at', '>', $after)
                    ->with('user')
                    ->orderBy('created_at')
                    ->get();

                foreach ($messages as $message) {
                    $after = $message->created_at->toISOString();

                    yield new \Illuminate\Http\StreamedEvent(
                        event: 'message',
                        data: json_encode([
                            'id' => $message->id,
                            'body' => $message->body,
                            'user' => ['id' => $message->user->id, 'name' => $message->user->name],
                            'created_at' => $message->created_at->toISOString(),
                        ]),
                    );
                }

                sleep(1);
            }
        });
    }
}
