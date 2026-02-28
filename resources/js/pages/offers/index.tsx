import { Head, Link, router } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { index, create, edit, destroy } from '@/routes/offers';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tilbud', href: index().url },
];

type Offer = {
    id: number;
    name: string;
    price: string;
    type: string;
    driving_lessons: number;
    theory_lessons: number;
};

type PaginatedOffers = {
    data: Offer[];
    links: { prev: string | null; next: string | null };
    meta: { from: number | null; to: number | null; total: number; last_page: number };
};

const typeLabels: Record<string, string> = {
    primary: 'Primær',
    addon: 'Tilvalg',
};

export default function OffersIndex({ offers }: { offers: PaginatedOffers }) {
    function handleDelete(offer: Offer) {
        if (confirm(`Er du sikker på, at du vil slette ${offer.name}?`)) {
            router.delete(destroy(offer).url);
        }
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tilbud" />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <Heading title="Tilbud" description="Administrer køreopladspakker" />
                    <Button asChild>
                        <Link href={create().url}>
                            <Plus className="mr-2 size-4" />
                            Opret tilbud
                        </Link>
                    </Button>
                </div>

                <div className="rounded-xl border">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b text-left">
                                <th className="px-4 py-3 font-medium">Navn</th>
                                <th className="px-4 py-3 font-medium">Type</th>
                                <th className="px-4 py-3 font-medium">Pris</th>
                                <th className="px-4 py-3 font-medium">Køretimer</th>
                                <th className="px-4 py-3 font-medium">Teorilektioner</th>
                                <th className="px-4 py-3 font-medium"></th>
                            </tr>
                        </thead>
                        <tbody>
                            {offers.data.map((offer) => (
                                <tr key={offer.id} className="border-b last:border-0">
                                    <td className="px-4 py-3 font-medium">{offer.name}</td>
                                    <td className="px-4 py-3">
                                        <Badge variant={offer.type === 'primary' ? 'default' : 'secondary'}>
                                            {typeLabels[offer.type] ?? offer.type}
                                        </Badge>
                                    </td>
                                    <td className="px-4 py-3">{Number(offer.price).toLocaleString('da-DK')} kr.</td>
                                    <td className="px-4 py-3 text-muted-foreground">{offer.driving_lessons}</td>
                                    <td className="px-4 py-3 text-muted-foreground">{offer.theory_lessons}</td>
                                    <td className="px-4 py-3 text-right">
                                        <div className="flex justify-end gap-2">
                                            <Button variant="ghost" size="sm" asChild>
                                                <Link href={edit(offer).url}>
                                                    <Pencil className="size-4" />
                                                </Link>
                                            </Button>
                                            <Button variant="ghost" size="sm" onClick={() => handleDelete(offer)}>
                                                <Trash2 className="size-4" />
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                            {offers.data.length === 0 && (
                                <tr>
                                    <td colSpan={6} className="px-4 py-8 text-center text-muted-foreground">
                                        Ingen tilbud fundet.
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
