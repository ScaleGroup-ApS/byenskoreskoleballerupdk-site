import { Head, Link, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { index, create, show } from '@/routes/students';
import type { BreadcrumbItem, PaginatedStudents } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Elever',
        href: index().url,
    },
];

const statusLabels: Record<string, string> = {
    active: 'Aktiv',
    inactive: 'Inaktiv',
    graduated: 'Udlært',
    dropped_out: 'Frafaldet',
};

const statusVariants: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    active: 'default',
    inactive: 'secondary',
    graduated: 'outline',
    dropped_out: 'destructive',
};

export default function StudentsIndex({ students }: { students: PaginatedStudents }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Elever" />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <Heading title="Elever" description="Administrer elever" />
                    <Button asChild>
                        <Link href={create().url}>
                            <Plus className="mr-2 size-4" />
                            Opret elev
                        </Link>
                    </Button>
                </div>

                <div className="rounded-xl border">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b text-left">
                                <th className="px-4 py-3 font-medium">Navn</th>
                                <th className="px-4 py-3 font-medium">Email</th>
                                <th className="px-4 py-3 font-medium">Telefon</th>
                                <th className="px-4 py-3 font-medium">Status</th>
                                <th className="px-4 py-3 font-medium">Startdato</th>
                            </tr>
                        </thead>
                        <tbody>
                            {students.data.map((student) => (
                                <tr
                                    key={student.id}
                                    className="cursor-pointer border-b transition-colors hover:bg-muted/50 last:border-0"
                                    onClick={() => router.visit(show(student).url)}
                                >
                                    <td className="px-4 py-3 font-medium">{student.user.name}</td>
                                    <td className="px-4 py-3 text-muted-foreground">{student.user.email}</td>
                                    <td className="px-4 py-3 text-muted-foreground">{student.phone ?? '-'}</td>
                                    <td className="px-4 py-3">
                                        <Badge variant={statusVariants[student.status] ?? 'secondary'}>
                                            {statusLabels[student.status] ?? student.status}
                                        </Badge>
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground">{student.start_date ?? '-'}</td>
                                </tr>
                            ))}
                            {students.data.length === 0 && (
                                <tr>
                                    <td colSpan={5} className="px-4 py-8 text-center text-muted-foreground">
                                        Ingen elever fundet.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                {students.meta.last_page > 1 && (
                    <div className="flex items-center justify-between">
                        <p className="text-sm text-muted-foreground">
                            Viser {students.meta.from}-{students.meta.to} af {students.meta.total} elever
                        </p>
                        <div className="flex gap-2">
                            {students.links.prev && (
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={students.links.prev}>Forrige</Link>
                                </Button>
                            )}
                            {students.links.next && (
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={students.links.next}>Næste</Link>
                                </Button>
                            )}
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
