import { useEffect, useRef } from 'react';
import type { Message } from '@/types/chat';

type Props = {
    messages: Message[];
    currentUserId: number;
};

export default function MessageThread({ messages, currentUserId }: Props) {
    const bottomRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        bottomRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages]);

    return (
        <div className="flex flex-1 flex-col gap-2 overflow-y-auto p-4">
            {messages.length === 0 && (
                <div className="m-auto text-sm text-muted-foreground">Ingen beskeder endnu.</div>
            )}
            {messages.map((message) => {
                const isOwn = message.user.id === currentUserId;
                return (
                    <div key={message.id} className={`flex flex-col gap-0.5 ${isOwn ? 'items-end' : 'items-start'}`}>
                        {!isOwn && (
                            <span className="text-xs text-muted-foreground">{message.user.name}</span>
                        )}
                        <div
                            className={`max-w-xs rounded-xl px-3 py-2 text-sm ${
                                isOwn ? 'bg-primary text-primary-foreground' : 'bg-muted'
                            }`}
                        >
                            {message.body}
                        </div>
                        <span className="text-xs text-muted-foreground">
                            {new Date(message.created_at).toLocaleTimeString('da-DK', {
                                hour: '2-digit',
                                minute: '2-digit',
                            })}
                        </span>
                    </div>
                );
            })}
            <div ref={bottomRef} />
        </div>
    );
}
