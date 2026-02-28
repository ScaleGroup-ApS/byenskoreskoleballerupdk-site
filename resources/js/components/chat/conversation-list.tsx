import { Badge } from '@/components/ui/badge';
import type { Conversation } from '@/types/chat';

type Props = {
    conversations: Conversation[];
    activeId: number | null;
    onSelect: (id: number) => void;
};

export default function ConversationList({ conversations, activeId, onSelect }: Props) {
    return (
        <div className="flex flex-col divide-y overflow-y-auto">
            {conversations.length === 0 && (
                <div className="px-4 py-6 text-center text-sm text-muted-foreground">
                    Ingen samtaler endnu.
                </div>
            )}
            {conversations.map((conversation) => (
                <button
                    key={conversation.id}
                    onClick={() => onSelect(conversation.id)}
                    className={`flex w-full flex-col gap-1 px-4 py-3 text-left transition-colors hover:bg-muted/50 ${activeId === conversation.id ? 'bg-muted' : ''}`}
                >
                    <div className="flex items-center justify-between">
                        <span className="font-medium">{conversation.name}</span>
                        {conversation.unread > 0 && (
                            <Badge variant="default" className="text-xs">
                                {conversation.unread}
                            </Badge>
                        )}
                    </div>
                    {conversation.last_message && (
                        <span className="truncate text-xs text-muted-foreground">
                            {conversation.last_message.user_name}: {conversation.last_message.body}
                        </span>
                    )}
                </button>
            ))}
        </div>
    );
}
