import { Head, Link, router } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { index, create, destroy } from '@/routes/payments';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Betalinger', href: index().url },
];

type Payment = {
    id: number;
    student: { user: { name: string } };
    amount: string;
    method: string;
    recorded_at: string;
    notes: string | null;
};

type PaginatedPayments = {
    data: Payment[];
    links: { prev: string | null; next: string | null };
    meta: { from: number | null; to: number | null; total: number; last_page: number };
};

const methodLabels: Record<string, string> = {
    cash: 'Kontant',
    card: 'Kort',
    mobile_pay: 'MobilePay',
    invoice: 'Faktura',
};

export default function PaymentsIndex({ payments }: { payments: PaginatedPayments }) {
    function handleDelete(payment: Payment) {
        if (confirm('Er du sikker på, at du vil slette denne betaling?')) {
            router.delete(destroy(payment).url);
        }
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Betalinger" />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <Heading title="Betalinger" description="Registrerede betalinger fra elever" />
                    <Button asChild>
                        <Link href={create().url}>
                            <Plus className="mr-2 size-4" />
                            Registrer betaling
                        </Link>
                    </Button>
                </div>

                <div className="rounded-xl border">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b text-left">
                                <th className="px-4 py-3 font-medium">Elev</th>
                                <th className="px-4 py-3 font-medium">Beløb</th>
                                <th className="px-4 py-3 font-medium">Metode</th>
                                <th className="px-4 py-3 font-medium">Dato</th>
                                <th className="px-4 py-3 font-medium"></th>
                            </tr>
                        </thead>
                        <tbody>
                            {payments.data.map((payment) => (
                                <tr key={payment.id} className="border-b last:border-0">
                                    <td className="px-4 py-3 font-medium">{payment.student.user.name}</td>
                                    <td className="px-4 py-3">{Number(payment.amount).toLocaleString('da-DK')} kr.</td>
                                    <td className="px-4 py-3">{methodLabels[payment.method] ?? payment.method}</td>
                                    <td className="px-4 py-3 text-muted-foreground">
                                        {new Date(payment.recorded_at).toLocaleDateString('da-DK')}
                                    </td>
                                    <td className="px-4 py-3 text-right">
                                        <Button variant="ghost" size="sm" onClick={() => handleDelete(payment)}>
                                            <Trash2 className="size-4" />
                                        </Button>
                                    </td>
                                </tr>
                            ))}
                            {payments.data.length === 0 && (
                                <tr>
                                    <td colSpan={5} className="px-4 py-8 text-center text-muted-foreground">
                                        Ingen betalinger fundet.
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
