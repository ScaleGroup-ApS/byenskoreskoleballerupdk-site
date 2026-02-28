<?php

namespace App\Http\Controllers\Chat;

use App\Enums\ConversationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\StoreConversationRequest;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ConversationController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Conversation::class);

        $conversations = auth()->user()
            ->conversations()
            ->with(['users', 'lastMessage.user'])
            ->latest()
            ->get()
            ->map(fn (Conversation $conversation) => [
                'id' => $conversation->id,
                'name' => $this->resolveConversationName($conversation),
                'type' => $conversation->type->value,
                'last_message' => $conversation->lastMessage ? [
                    'body' => $conversation->lastMessage->body,
                    'user_name' => $conversation->lastMessage->user->name,
                    'created_at' => $conversation->lastMessage->created_at->toISOString(),
                ] : null,
                'unread' => $this->resolveUnreadCount($conversation),
            ]);

        return Inertia::render('chat/index', [
            'conversations' => $conversations,
        ]);
    }

    public function store(StoreConversationRequest $request): RedirectResponse
    {
        $this->authorize('create', Conversation::class);

        $data = $request->validated();

        if (isset($data['user_id'])) {
            $otherUser = User::findOrFail($data['user_id']);

            $existing = auth()->user()
                ->conversations()
                ->where('type', ConversationType::Direct)
                ->whereHas('users', fn ($q) => $q->where('user_id', $otherUser->id))
                ->first();

            if ($existing) {
                return redirect()->route('chat.index', ['conversation' => $existing->id]);
            }

            $conversation = Conversation::create(['type' => ConversationType::Direct]);
            $conversation->users()->attach([auth()->id(), $otherUser->id]);
        } else {
            $conversation = Conversation::create([
                'type' => ConversationType::Group,
                'name' => $data['name'],
            ]);
            $conversation->users()->attach(auth()->id());
        }

        return redirect()->route('chat.index', ['conversation' => $conversation->id]);
    }

    private function resolveConversationName(Conversation $conversation): string
    {
        if ($conversation->type === ConversationType::Group) {
            return $conversation->name ?? 'Gruppesamtale';
        }

        $other = $conversation->users
            ->firstWhere('id', '!=', auth()->id());

        return $other?->name ?? 'Direkte besked';
    }

    private function resolveUnreadCount(Conversation $conversation): int
    {
        $pivot = $conversation->users
            ->firstWhere('id', auth()->id())
            ?->pivot;

        if (! $pivot || ! $pivot->last_read_at) {
            return $conversation->messages()->count();
        }

        return $conversation->messages()
            ->where('created_at', '>', $pivot->last_read_at)
            ->count();
    }
}
