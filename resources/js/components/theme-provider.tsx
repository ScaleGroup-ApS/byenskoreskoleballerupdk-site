import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';

type BrandingColors = {
    primary?: string;
    sidebar?: string;
    accent?: string;
};

type SharedProps = {
    branding: {
        name: string;
        logo: string | null;
        colors: BrandingColors;
    };
};

export default function ThemeProvider({ children }: { children: ReactNode }) {
    const { branding } = usePage<SharedProps>().props;
    const colors = branding?.colors ?? {};

    const hasOverrides = Object.keys(colors).length > 0;

    if (!hasOverrides) {
        return <>{children}</>;
    }

    const cssVars: Record<string, string> = {};

    if (colors.primary) {
        cssVars['--brand-primary'] = colors.primary;
    }
    if (colors.sidebar) {
        cssVars['--brand-sidebar'] = colors.sidebar;
    }
    if (colors.accent) {
        cssVars['--brand-accent'] = colors.accent;
    }

    return <div style={cssVars as React.CSSProperties}>{children}</div>;
}
