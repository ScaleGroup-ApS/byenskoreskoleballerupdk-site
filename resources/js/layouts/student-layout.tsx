import { LayoutGrid } from 'lucide-react';
import { AppContent } from '@/components/app-content';
import { AppHeader } from '@/components/app-header';
import { AppShell } from '@/components/app-shell';
import ThemeProvider from '@/components/theme-provider';
import { dashboard } from '@/routes/student';
import type { AppLayoutProps, NavItem } from '@/types';

const navItems: NavItem[] = [
    { title: 'Dashboard', href: dashboard(), icon: LayoutGrid },
];

export default function StudentLayout({ children, breadcrumbs }: AppLayoutProps) {
    return (
        <ThemeProvider>
            <AppShell>
                <AppHeader breadcrumbs={breadcrumbs} navItems={navItems} />
                <AppContent>{children}</AppContent>
            </AppShell>
        </ThemeProvider>
    );
}
