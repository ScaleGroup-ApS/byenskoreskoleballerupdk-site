import { Head, useForm } from '@inertiajs/react';
import { store } from '@/actions/App/Http/Controllers/Offers/OfferController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { index } from '@/routes/offers';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tilbud', href: index().url },
    { title: 'Opret tilbud', href: '#' },
];

type OfferType = { value: string; label: string };

export default function OfferCreate({ offerTypes }: { offerTypes: OfferType[] }) {
    const form = useForm({
        name: '',
        description: '',
        price: '',
        type: 'primary',
        theory_lessons: '0',
        driving_lessons: '0',
        track_required: false,
        slippery_required: false,
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        form.submit(store());
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Opret tilbud" />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <Heading title="Opret tilbud" />

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
                        <Label htmlFor="type">Type</Label>
                        <select
                            id="type"
                            value={form.data.type}
                            onChange={(e) => form.setData('type', e.target.value)}
                            className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
                        >
                            {offerTypes.map((t) => (
                                <option key={t.value} value={t.value}>{t.label}</option>
                            ))}
                        </select>
                        <InputError message={form.errors.type} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="price">Pris (kr.)</Label>
                        <Input
                            id="price"
                            type="number"
                            min="0"
                            step="0.01"
                            value={form.data.price}
                            onChange={(e) => form.setData('price', e.target.value)}
                            required
                        />
                        <InputError message={form.errors.price} />
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="theory_lessons">Teorilektioner</Label>
                            <Input
                                id="theory_lessons"
                                type="number"
                                min="0"
                                value={form.data.theory_lessons}
                                onChange={(e) => form.setData('theory_lessons', e.target.value)}
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="driving_lessons">Køretimer</Label>
                            <Input
                                id="driving_lessons"
                                type="number"
                                min="0"
                                value={form.data.driving_lessons}
                                onChange={(e) => form.setData('driving_lessons', e.target.value)}
                            />
                        </div>
                    </div>

                    <div className="space-y-2">
                        <div className="flex items-center gap-2">
                            <Checkbox
                                id="track_required"
                                checked={form.data.track_required}
                                onCheckedChange={(v) => form.setData('track_required', v === true)}
                            />
                            <Label htmlFor="track_required">Bane krævet</Label>
                        </div>
                        <div className="flex items-center gap-2">
                            <Checkbox
                                id="slippery_required"
                                checked={form.data.slippery_required}
                                onCheckedChange={(v) => form.setData('slippery_required', v === true)}
                            />
                            <Label htmlFor="slippery_required">Glat bane krævet</Label>
                        </div>
                    </div>

                    <Button disabled={form.processing}>Opret tilbud</Button>
                </form>
            </div>
        </AppLayout>
    );
}
