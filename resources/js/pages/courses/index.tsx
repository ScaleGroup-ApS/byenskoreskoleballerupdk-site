import { Head } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import { format } from 'date-fns';
import { da } from 'date-fns/locale';
import { show } from '@/actions/App/Http/Controllers/Courses/CourseController';
import Heading from '@/components/heading';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type Course = {
    id: number;
    start_at: string;
    end_at: string;
    offer: { id: number; name: string };
    enrollments_count: number;
    max_students: number | null;
};

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Kurser', href: '#' }];

export default function CoursesIndex({ courses }: { courses: Course[] }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Kurser" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <Heading title="Kurser" />

                {courses.length === 0 ? (
                    <div className="rounded-xl border px-4 py-10 text-center text-sm text-muted-foreground">
                        Ingen kommende kurser.
                    </div>
                ) : (
                    <div className="rounded-xl border">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b text-left text-muted-foreground">
                                    <th className="px-4 py-3 font-medium">Dato</th>
                                    <th className="px-4 py-3 font-medium">Tilbud</th>
                                    <th className="px-4 py-3 font-medium">Tilmeldte</th>
                                    <th className="px-4 py-3" />
                                </tr>
                            </thead>
                            <tbody>
                                {courses.map((course) => (
                                    <tr key={course.id} className="border-b last:border-0">
                                        <td className="px-4 py-3">
                                            {format(new Date(course.start_at), 'PPP', { locale: da })}
                                        </td>
                                        <td className="px-4 py-3">{course.offer.name}</td>
                                        <td className="px-4 py-3">
                                            {course.enrollments_count}
                                            {course.max_students != null && ` / ${course.max_students}`}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <Link
                                                href={show({ course: course.id }).url}
                                                className="text-primary hover:underline"
                                            >
                                                Se kursus
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
