import { Head, Link, useForm, Form } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { update } from '@/actions/App/Http/Controllers/Courses/CourseController';
import { approve } from '@/actions/App/Http/Controllers/Enrollment/EnrollmentApprovalController';
import { destroy } from '@/actions/App/Http/Controllers/Offers/CourseController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { index } from '@/routes/courses';
import type { BreadcrumbItem } from '@/types';

type Enrollment = {
    id: number;
    status: string;
    payment_method: string;
    student: { id: number; name: string; email: string };
};

type CourseDetail = {
    id: number;
    start_at: string;
    end_at: string;
    max_students: number | null;
    offer: { id: number; name: string };
    enrollments: Enrollment[];
};

export default function CourseShow({ course }: { course: CourseDetail }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Kurser', href: index().url },
        { title: course.offer.name, href: '#' },
    ];

    const form = useForm({
        start_at: new Date(course.start_at).toISOString().slice(0, 16),
        end_at: new Date(course.end_at).toISOString().slice(0, 16),
        max_students: course.max_students ? String(course.max_students) : '',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        form.submit(update(course));
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${course.offer.name} – kursus`} />

            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center gap-3">
                    <Link href={index().url} className="text-muted-foreground hover:text-foreground">
                        <ArrowLeft className="size-4" />
                    </Link>
                    <Heading title={course.offer.name} />
                </div>

                {/* Edit form */}
                <div className="max-w-lg">
                    <h2 className="mb-4 text-base font-semibold">Kursusdato</h2>
                    <form onSubmit={handleSubmit} className="space-y-4">
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="start_at">Start</Label>
                                <Input
                                    id="start_at"
                                    type="datetime-local"
                                    value={form.data.start_at}
                                    onChange={(e) => form.setData('start_at', e.target.value)}
                                    required
                                />
                                <InputError message={form.errors.start_at} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="end_at">Slut</Label>
                                <Input
                                    id="end_at"
                                    type="datetime-local"
                                    value={form.data.end_at}
                                    onChange={(e) => form.setData('end_at', e.target.value)}
                                    required
                                />
                                <InputError message={form.errors.end_at} />
                            </div>
                        </div>
                        <div className="grid max-w-[160px] gap-2">
                            <Label htmlFor="max_students">Maks. elever</Label>
                            <Input
                                id="max_students"
                                type="number"
                                min="1"
                                value={form.data.max_students}
                                onChange={(e) => form.setData('max_students', e.target.value)}
                                placeholder="Ingen grænse"
                            />
                            <InputError message={form.errors.max_students} />
                        </div>
                        <Button type="submit" disabled={form.processing}>
                            Gem ændringer
                        </Button>
                    </form>
                    <Form
                        {...destroy({ offer: course.offer, course })}
                        method="delete"
                        onBefore={() => confirm('Er du sikker på, at du vil slette dette kursus?')}
                    >
                        {({ processing }) => (
                            <Button type="submit" variant="destructive" disabled={processing}>
                                Slet kursus
                            </Button>
                        )}
                    </Form>
                </div>

                {/* Enrollments table */}
                <div className="max-w-2xl">
                    <h2 className="mb-4 text-base font-semibold">
                        Tilmeldte ({course.enrollments.length}
                        {course.max_students ? ` / ${course.max_students}` : ''})
                    </h2>

                    {course.enrollments.length === 0 ? (
                        <p className="text-sm text-muted-foreground">Ingen tilmeldte endnu.</p>
                    ) : (
                        <div className="rounded-md border">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50">
                                        <th className="px-4 py-2 text-left font-medium">Navn</th>
                                        <th className="px-4 py-2 text-left font-medium">E-mail</th>
                                        <th className="px-4 py-2 text-left font-medium">Betaling</th>
                                        <th className="px-4 py-2 text-left font-medium">Status</th>
                                        <th className="px-4 py-2" />
                                    </tr>
                                </thead>
                                <tbody>
                                    {course.enrollments.map((enrollment) => (
                                        <tr key={enrollment.id} className="border-b last:border-0">
                                            <td className="px-4 py-2">{enrollment.student.name}</td>
                                            <td className="px-4 py-2 text-muted-foreground">{enrollment.student.email}</td>
                                            <td className="px-4 py-2">{enrollment.payment_method === 'stripe' ? 'Kortbetaling' : 'Kontant'}</td>
                                            <td className="px-4 py-2">
                                                {enrollment.status === 'pending_approval' && 'Afventer godkendelse'}
                                                {enrollment.status === 'pending_payment' && 'Afventer betaling'}
                                                {enrollment.status === 'completed' && 'Godkendt'}
                                                {enrollment.status === 'rejected' && 'Afvist'}
                                            </td>
                                            <td className="px-4 py-2 text-right">
                                                {enrollment.status === 'pending_approval' && (
                                                    <Form {...approve.form({ enrollment })}>
                                                        {({ processing }) => (
                                                            <Button type="submit" size="sm" disabled={processing}>
                                                                Godkend
                                                            </Button>
                                                        )}
                                                    </Form>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
