import { Head, useForm } from '@inertiajs/react';
import { store } from '@/actions/App/Http/Controllers/Vehicles/VehicleController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { index } from '@/routes/vehicles';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Køretøjer', href: index().url },
    { title: 'Tilføj køretøj', href: '#' },
];

export default function VehicleCreate() {
    const form = useForm({
        name: '',
        plate_number: '',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        form.submit(store());
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tilføj køretøj" />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <Heading title="Tilføj køretøj" />

                <form onSubmit={handleSubmit} className="max-w-lg space-y-6">
                    <div className="grid gap-2">
                        <Label htmlFor="name">Navn</Label>
                        <Input
                            id="name"
                            value={form.data.name}
                            onChange={(e) => form.setData('name', e.target.value)}
                            required
                            placeholder="f.eks. Toyota Yaris #1"
                        />
                        <InputError message={form.errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="plate_number">Nummerplade</Label>
                        <Input
                            id="plate_number"
                            value={form.data.plate_number}
                            onChange={(e) => form.setData('plate_number', e.target.value)}
                            required
                            placeholder="AB12345"
                        />
                        <InputError message={form.errors.plate_number} />
                    </div>

                    <Button disabled={form.processing}>Tilføj køretøj</Button>
                </form>
            </div>
        </AppLayout>
    );
}
