import { SendHorizonal } from 'lucide-react';
import type { FormEvent} from 'react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type Props = {
    onSend: (body: string) => void;
    disabled?: boolean;
};

export default function MessageInput({ onSend, disabled = false }: Props) {
    const [body, setBody] = useState('');

    function handleSubmit(e: FormEvent) {
        e.preventDefault();
        const trimmed = body.trim();
        if (!trimmed) return;
        onSend(trimmed);
        setBody('');
    }

    return (
        <form onSubmit={handleSubmit} className="flex items-center gap-2 border-t p-3">
            <Input
                value={body}
                onChange={(e) => setBody(e.target.value)}
                placeholder="Skriv en besked..."
                disabled={disabled}
                className="flex-1"
            />
            <Button type="submit" size="icon" disabled={disabled || !body.trim()}>
                <SendHorizonal className="size-4" />
            </Button>
        </form>
    );
}
