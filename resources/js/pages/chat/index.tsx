import { Head, usePage } from '@inertiajs/react';
import { useEventStream } from '@laravel/stream-react';
import axios from 'axios';
import { useCallback, useEffect, useState } from 'react';
import { index as chatIndex } from '@/actions/App/Http/Controllers/Chat/ConversationController';
import { index as messagesIndex, store as messagesStore, stream as messagesStream } from '@/actions/App/Http/Controllers/Chat/MessageController';
import ConversationList from '@/components/chat/conversation-list';
import MessageInput from '@/components/chat/message-input';
import MessageThread from '@/components/chat/message-thread';
import Heading from '@/components/heading';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import type { Conversation, Message } from '@/types/chat';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Chat', href: chatIndex().url },
];

type Props = {
    conversations: Conversation[];
};

export default function ChatIndex({ conversations }: Props) {
    const { auth } = usePage().props as { auth: { user: { id: number; name: string } } };
    const [activeId, setActiveId] = useState<number | null>(conversations[0]?.id ?? null);
    const [messages, setMessages] = useState<Message[]>([]);
    const [loadingMessages, setLoadingMessages] = useState(false);

    const activeConversation = conversations.find((c) => c.id === activeId);
    const streamUrl = activeId ? messagesStream(activeId).url : '';

    const { close } = useEventStream(streamUrl, {
        eventName: 'message',
        onMessage: (event: MessageEvent) => {
            try {
                const message: Message = JSON.parse(event.data as string);
                setMessages((prev) => [...prev, message]);
            } catch {
                // ignore malformed events
            }
        },
    });

    useEffect(() => {
        if (!activeId) return;

        // eslint-disable-next-line react-hooks/set-state-in-effect
        setLoadingMessages(true);
         
        setMessages([]);

        axios
            .get<{ data: Message[] }>(messagesIndex(activeId).url)
            .then(({ data }) => {
                setMessages(data.data);
                setLoadingMessages(false);
            });

        return () => close();
    }, [activeId, close]);

    const sendMessage = useCallback(
        async (body: string) => {
            if (!activeId) return;

            const { data } = await axios.post<Message>(messagesStore(activeId).url, { body });
            setMessages((prev) => [...prev, data]);
        },
        [activeId],
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Chat" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <Heading title="Chat" description="Samtaler med instruktører og elever" />
                <div className="flex flex-1 overflow-hidden rounded-xl border">
                    <aside className="w-64 shrink-0 overflow-y-auto border-r">
                        <ConversationList
                            conversations={conversations}
                            activeId={activeId}
                            onSelect={setActiveId}
                        />
                    </aside>
                    <div className="flex flex-1 flex-col overflow-hidden">
                        {activeConversation ? (
                            <>
                                <div className="border-b px-4 py-3">
                                    <span className="font-semibold">{activeConversation.name}</span>
                                </div>
                                {loadingMessages ? (
                                    <div className="flex flex-1 items-center justify-center text-sm text-muted-foreground">
                                        Indlæser beskeder...
                                    </div>
                                ) : (
                                    <MessageThread messages={messages} currentUserId={auth.user.id} />
                                )}
                                <MessageInput onSend={sendMessage} />
                            </>
                        ) : (
                            <div className="flex flex-1 items-center justify-center text-sm text-muted-foreground">
                                Vælg en samtale for at starte.
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
