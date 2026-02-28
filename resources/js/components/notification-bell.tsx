import { usePage } from '@inertiajs/react';
import { Bell } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

type Notification = {
    id: string;
    type: string;
    data: {
        message: string;
        url?: string;
    };
    read_at: string | null;
    created_at: string;
};

type SharedProps = {
    auth: {
        notifications: Notification[];
        unread_count: number;
    };
};

export function NotificationBell() {
    const { auth } = usePage<SharedProps>().props;
    const notifications = auth?.notifications ?? [];
    const unreadCount = auth?.unread_count ?? 0;

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="icon" className="relative">
                    <Bell className="size-5" />
                    {unreadCount > 0 && (
                        <span className="absolute -top-0.5 -right-0.5 flex size-4 items-center justify-center rounded-full bg-destructive text-[10px] font-medium text-destructive-foreground">
                            {unreadCount > 9 ? '9+' : unreadCount}
                        </span>
                    )}
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-80">
                <DropdownMenuLabel>Notifikationer</DropdownMenuLabel>
                <DropdownMenuSeparator />
                {notifications.length === 0 ? (
                    <DropdownMenuItem disabled>Ingen nye notifikationer</DropdownMenuItem>
                ) : (
                    notifications.map((notification) => (
                        <DropdownMenuItem key={notification.id} className="flex flex-col items-start gap-1">
                            <span className="text-sm">{notification.data.message}</span>
                            <span className="text-xs text-muted-foreground">
                                {new Date(notification.created_at).toLocaleDateString('da-DK')}
                            </span>
                        </DropdownMenuItem>
                    ))
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
