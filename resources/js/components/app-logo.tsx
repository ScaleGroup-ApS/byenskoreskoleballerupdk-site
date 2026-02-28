import { usePage } from '@inertiajs/react';
import AppLogoIcon from './app-logo-icon';

type SharedProps = {
    branding: {
        name: string;
        logo: string | null;
    };
};

export default function AppLogo() {
    const { branding } = usePage<SharedProps>().props;

    return (
        <>
            <div className="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground">
                {branding?.logo ? (
                    <img src={branding.logo} alt={branding.name} className="size-5 object-contain" />
                ) : (
                    <AppLogoIcon className="size-5 fill-current text-white dark:text-black" />
                )}
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-tight font-semibold">{branding?.name ?? 'Laravel Starter Kit'}</span>
            </div>
        </>
    );
}
