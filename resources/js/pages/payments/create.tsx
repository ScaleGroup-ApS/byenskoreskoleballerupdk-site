import { Head, useForm } from '@inertiajs/react';
import { store } from '@/actions/App/Http/Controllers/Payments/PaymentController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { index } from '@/routes/payments';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Betalinger', href: index().url },
    { title: 'Registrer betaling', href: '#' },
];

type Student = { id: number; user: { name: string } };
type PaymentMethod = { value: string; label: string };

export default function PaymentCreate({
    students,
    paymentMethods,
}: {
    students: Student[];
    paymentMethods: PaymentMethod[];
}) {
    const form = useForm({
        student_id: '',
        amount: '',
        method: 'card',
        notes: '',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        form.submit(store());
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Registrer betaling" />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <Heading title="Registrer betaling" />

                <form onSubmit={handleSubmit} className="max-w-lg space-y-6">
                    <div className="grid gap-2">
                        <Label htmlFor="student_id">Elev</Label>
                        <select
                            id="student_id"
                            value={form.data.student_id}
                            onChange={(e) => form.setData('student_id', e.target.value)}
                            className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
                            required
                        >
                            <option value="">Vælg elev...</option>
                            {students.map((s) => (
                                <option key={s.id} value={s.id}>{s.user.name}</option>
                            ))}
                        </select>
                        <InputError message={form.errors.student_id} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="amount">Beløb (kr.)</Label>
                        <Input
                            id="amount"
                            type="number"
                            min="0.01"
                            step="0.01"
                            value={form.data.amount}
                            onChange={(e) => form.setData('amount', e.target.value)}
                            required
                        />
                        <InputError message={form.errors.amount} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="method">Betalingsmetode</Label>
                        <select
                            id="method"
                            value={form.data.method}
                            onChange={(e) => form.setData('method', e.target.value)}
                            className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
                        >
                            {paymentMethods.map((m) => (
                                <option key={m.value} value={m.value}>{m.label}</option>
                            ))}
                        </select>
                        <InputError message={form.errors.method} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="notes">Notat</Label>
                        <textarea
                            id="notes"
                            value={form.data.notes}
                            onChange={(e) => form.setData('notes', e.target.value)}
                            rows={3}
                            className="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm"
                        />
                    </div>

                    <Button disabled={form.processing}>Registrer betaling</Button>
                </form>
            </div>
        </AppLayout>
    );
}
