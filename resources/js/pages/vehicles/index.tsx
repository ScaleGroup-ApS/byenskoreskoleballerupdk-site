import { Head, Link, router } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { index, create, edit, destroy } from '@/routes/vehicles';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Køretøjer', href: index().url },
];

type Vehicle = {
    id: number;
    name: string;
    plate_number: string;
    active: boolean;
};

type PaginatedVehicles = {
    data: Vehicle[];
    links: { prev: string | null; next: string | null };
    meta: { from: number | null; to: number | null; total: number; last_page: number };
};

export default function VehiclesIndex({ vehicles }: { vehicles: PaginatedVehicles }) {
    function handleDelete(vehicle: Vehicle) {
        if (confirm(`Er du sikker på, at du vil slette ${vehicle.name}?`)) {
            router.delete(destroy(vehicle).url);
        }
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Køretøjer" />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <Heading title="Køretøjer" description="Administrer køretøjer" />
                    <Button asChild>
                        <Link href={create().url}>
                            <Plus className="mr-2 size-4" />
                            Tilføj køretøj
                        </Link>
                    </Button>
                </div>

                <div className="rounded-xl border">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b text-left">
                                <th className="px-4 py-3 font-medium">Navn</th>
                                <th className="px-4 py-3 font-medium">Nummerplade</th>
                                <th className="px-4 py-3 font-medium">Status</th>
                                <th className="px-4 py-3 font-medium"></th>
                            </tr>
                        </thead>
                        <tbody>
                            {vehicles.data.map((vehicle) => (
                                <tr key={vehicle.id} className="border-b last:border-0">
                                    <td className="px-4 py-3 font-medium">{vehicle.name}</td>
                                    <td className="px-4 py-3 text-muted-foreground">{vehicle.plate_number}</td>
                                    <td className="px-4 py-3">
                                        <Badge variant={vehicle.active ? 'default' : 'secondary'}>
                                            {vehicle.active ? 'Aktiv' : 'Inaktiv'}
                                        </Badge>
                                    </td>
                                    <td className="px-4 py-3 text-right">
                                        <div className="flex justify-end gap-2">
                                            <Button variant="ghost" size="sm" asChild>
                                                <Link href={edit(vehicle).url}>
                                                    <Pencil className="size-4" />
                                                </Link>
                                            </Button>
                                            <Button variant="ghost" size="sm" onClick={() => handleDelete(vehicle)}>
                                                <Trash2 className="size-4" />
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                            {vehicles.data.length === 0 && (
                                <tr>
                                    <td colSpan={4} className="px-4 py-8 text-center text-muted-foreground">
                                        Ingen køretøjer fundet.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </AppLayout>
    );
}
