export type ConversationUser = {
    id: number;
    name: string;
};

export type LastMessage = {
    body: string;
    user_name: string;
    created_at: string;
};

export type Conversation = {
    id: number;
    name: string;
    type: 'direct' | 'group';
    last_message: LastMessage | null;
    unread: number;
};

export type Message = {
    id: number;
    body: string;
    user: { id: number; name: string };
    created_at: string;
};
