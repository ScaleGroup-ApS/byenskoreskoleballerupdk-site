import { Head, useForm } from '@inertiajs/react';
import { update } from '@/actions/App/Http/Controllers/Students/StudentController';
import Heading from '@/components/heading';
import { useLoginLink } from '@/hooks/use-login-link';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { index, show } from '@/routes/students';
import type { BreadcrumbItem, Student } from '@/types';

export default function StudentEdit({ student }: { student: Student }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Elever', href: index().url },
        { title: student.user.name, href: show(student).url },
        { title: 'Rediger', href: '#' },
    ];

    const { sendLoginLink, processing: loginLinkProcessing } = useLoginLink(student);

    const form = useForm({
        name: student.user.name,
        email: student.user.email,
        phone: student.phone ?? '',
        cpr: '',
        status: student.status,
        start_date: student.start_date ?? '',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        form.submit(update(student));
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Rediger ${student.user.name}`} />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <Heading title={`Rediger ${student.user.name}`} />

                <div className="max-w-lg">
                    <Button
                        variant="outline"
                        type="button"
                        onClick={sendLoginLink}
                        disabled={loginLinkProcessing}
                    >
                        Send login link
                    </Button>
                </div>

                <form onSubmit={handleSubmit} className="max-w-lg space-y-6">
                    <div className="grid gap-2">
                        <Label htmlFor="name">Navn</Label>
                        <Input
                            id="name"
                            value={form.data.name}
                            onChange={(e) => form.setData('name', e.target.value)}
                            required
                        />
                        <InputError message={form.errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="email">Email</Label>
                        <Input
                            id="email"
                            type="email"
                            value={form.data.email}
                            onChange={(e) => form.setData('email', e.target.value)}
                            required
                        />
                        <InputError message={form.errors.email} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="phone">Telefon</Label>
                        <Input
                            id="phone"
                            value={form.data.phone}
                            onChange={(e) => form.setData('phone', e.target.value)}
                        />
                        <InputError message={form.errors.phone} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="cpr">CPR-nummer</Label>
                        <Input
                            id="cpr"
                            value={form.data.cpr}
                            onChange={(e) => form.setData('cpr', e.target.value)}
                            placeholder="Lad stå tom for at beholde eksisterende"
                        />
                        <InputError message={form.errors.cpr} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="start_date">Startdato</Label>
                        <Input
                            id="start_date"
                            type="date"
                            value={form.data.start_date}
                            onChange={(e) => form.setData('start_date', e.target.value)}
                        />
                        <InputError message={form.errors.start_date} />
                    </div>

                    <Button disabled={form.processing}>Gem ændringer</Button>
                </form>
            </div>
        </AppLayout>
    );
}
