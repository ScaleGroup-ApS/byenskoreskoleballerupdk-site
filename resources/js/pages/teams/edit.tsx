import { Head, useForm } from '@inertiajs/react';
import { update } from '@/actions/App/Http/Controllers/Teams/TeamController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { index, show } from '@/routes/teams';
import type { BreadcrumbItem } from '@/types';

type Team = {
    id: number;
    name: string;
    description: string | null;
};

export default function TeamEdit({ team }: { team: Team }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Hold', href: index().url },
        { title: team.name, href: show(team).url },
        { title: 'Rediger', href: '#' },
    ];

    const form = useForm({
        name: team.name,
        description: team.description ?? '',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        form.submit(update(team));
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Rediger ${team.name}`} />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <Heading title={`Rediger ${team.name}`} />

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
                        <Label htmlFor="description">Beskrivelse</Label>
                        <textarea
                            id="description"
                            value={form.data.description}
                            onChange={(e) => form.setData('description', e.target.value)}
                            className="flex min-h-20 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm transition-colors placeholder:text-muted-foreground"
                        />
                        <InputError message={form.errors.description} />
                    </div>

                    <Button disabled={form.processing}>Gem ændringer</Button>
                </form>
            </div>
        </AppLayout>
    );
}
