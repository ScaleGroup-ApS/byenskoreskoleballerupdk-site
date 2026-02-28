import { Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { index, create, show } from '@/routes/teams';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Hold', href: index().url },
];

type Team = {
    id: number;
    name: string;
    description: string | null;
    students_count: number;
};

type PaginatedTeams = {
    data: Team[];
    links: { prev: string | null; next: string | null };
    meta: { from: number | null; to: number | null; total: number; last_page: number };
};

export default function TeamsIndex({ teams }: { teams: PaginatedTeams }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Hold" />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <Heading title="Hold" description="Administrer hold" />
                    <Button asChild>
                        <Link href={create().url}>
                            <Plus className="mr-2 size-4" />
                            Opret hold
                        </Link>
                    </Button>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {teams.data.map((team) => (
                        <Link
                            key={team.id}
                            href={show(team).url}
                            className="rounded-xl border p-4 transition-colors hover:bg-muted/50"
                        >
                            <h3 className="font-medium">{team.name}</h3>
                            {team.description && (
                                <p className="mt-1 text-sm text-muted-foreground">{team.description}</p>
                            )}
                            <p className="mt-2 text-sm text-muted-foreground">
                                {team.students_count} {team.students_count === 1 ? 'elev' : 'elever'}
                            </p>
                        </Link>
                    ))}
                    {teams.data.length === 0 && (
                        <p className="text-sm text-muted-foreground">Ingen hold oprettet endnu.</p>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
