import { Head, Link, router } from '@inertiajs/react';
import { Pencil, Trash2 } from 'lucide-react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { show as studentShow } from '@/routes/students';
import { index, show, edit, destroy } from '@/routes/teams';
import type { BreadcrumbItem, Student } from '@/types';

type Team = {
    id: number;
    name: string;
    description: string | null;
    students: Student[];
};

export default function TeamShow({ team }: { team: Team }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Hold', href: index().url },
        { title: team.name, href: show(team).url },
    ];

    function handleDelete() {
        if (confirm('Er du sikker på, at du vil slette dette hold?')) {
            router.delete(destroy(team).url);
        }
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={team.name} />

            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <Heading title={team.name} description={team.description ?? undefined} />
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href={edit(team).url}>
                                <Pencil className="mr-2 size-4" />
                                Rediger
                            </Link>
                        </Button>
                        <Button variant="destructive" onClick={handleDelete}>
                            <Trash2 className="mr-2 size-4" />
                            Slet
                        </Button>
                    </div>
                </div>

                <div className="max-w-lg space-y-4">
                    <Heading variant="small" title="Elever" />

                    {team.students.length > 0 ? (
                        <ul className="space-y-2">
                            {team.students.map((student) => (
                                <li key={student.id}>
                                    <Link
                                        href={studentShow(student).url}
                                        className="flex items-center justify-between rounded-lg border px-4 py-3 text-sm hover:bg-muted/50"
                                    >
                                        <span className="font-medium">{student.user.name}</span>
                                        <span className="text-muted-foreground">{student.user.email}</span>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <p className="text-sm text-muted-foreground">Ingen elever i dette hold.</p>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
