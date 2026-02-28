import { Head, useForm } from '@inertiajs/react';
import { update } from '@/actions/App/Http/Controllers/Vehicles/VehicleController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { index } from '@/routes/vehicles';
import type { BreadcrumbItem } from '@/types';

type Vehicle = {
    id: number;
    name: string;
    plate_number: string;
    active: boolean;
};

export default function VehicleEdit({ vehicle }: { vehicle: Vehicle }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Køretøjer', href: index().url },
        { title: vehicle.name, href: '#' },
    ];

    const form = useForm({
        name: vehicle.name,
        plate_number: vehicle.plate_number,
        active: vehicle.active,
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        form.submit(update(vehicle));
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Rediger ${vehicle.name}`} />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <Heading title={`Rediger ${vehicle.name}`} />

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
                        <Label htmlFor="plate_number">Nummerplade</Label>
                        <Input
                            id="plate_number"
                            value={form.data.plate_number}
                            onChange={(e) => form.setData('plate_number', e.target.value)}
                            required
                        />
                        <InputError message={form.errors.plate_number} />
                    </div>

                    <div className="flex items-center gap-2">
                        <Checkbox
                            id="active"
                            checked={form.data.active}
                            onCheckedChange={(checked) => form.setData('active', checked === true)}
                        />
                        <Label htmlFor="active">Aktiv</Label>
                    </div>

                    <Button disabled={form.processing}>Gem ændringer</Button>
                </form>
            </div>
        </AppLayout>
    );
}
