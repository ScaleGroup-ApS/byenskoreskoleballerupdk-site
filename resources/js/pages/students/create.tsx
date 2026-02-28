import { Head, useForm } from '@inertiajs/react';
import { store } from '@/actions/App/Http/Controllers/Students/StudentController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { index } from '@/routes/students';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Elever', href: index().url },
    { title: 'Opret elev', href: '#' },
];

export default function StudentCreate() {
    const form = useForm({
        name: '',
        email: '',
        phone: '',
        cpr: '',
        start_date: '',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        form.submit(store());
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Opret elev" />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <Heading title="Opret elev" description="Tilføj en ny elev til systemet" />

                <form onSubmit={handleSubmit} className="max-w-lg space-y-6">
                    <div className="grid gap-2">
                        <Label htmlFor="name">Navn</Label>
                        <Input
                            id="name"
                            value={form.data.name}
                            onChange={(e) => form.setData('name', e.target.value)}
                            required
                            placeholder="Fulde navn"
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
                            placeholder="DDMMÅÅ-XXXX"
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

                    <Button disabled={form.processing}>Opret elev</Button>
                </form>
            </div>
        </AppLayout>
    );
}
