import { Form, Head } from '@inertiajs/react';
import { CheckCircle2, XCircle } from 'lucide-react';
import { useState } from 'react';
import { index, approve, reject } from '@/actions/App/Http/Controllers/Enrollment/EnrollmentApprovalController';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tilmeldinger', href: index().url },
];

type Enrollment = {
    id: number;
    student_name: string;
    student_email: string;
    offer_name: string;
    payment_method: 'stripe' | 'cash';
    status: 'pending_payment' | 'pending_approval';
    created_at: string;
};

const methodLabels: Record<string, string> = {
    stripe: 'Kortbetaling',
    cash: 'Kontant',
};

const statusLabels: Record<string, string> = {
    pending_payment: 'Afventer betaling',
    pending_approval: 'Afventer godkendelse',
};

export default function EnrollmentsIndex({ enrollments }: { enrollments: Enrollment[] }) {
    const [rejectTarget, setRejectTarget] = useState<Enrollment | null>(null);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tilmeldinger" />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <Heading title="Tilmeldinger" description="Afventende tilmeldinger der kræver godkendelse eller betaling" />

                <div className="rounded-xl border">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b text-left">
                                <th className="px-4 py-3 font-medium">Elev</th>
                                <th className="px-4 py-3 font-medium">Pakke</th>
                                <th className="px-4 py-3 font-medium">Metode</th>
                                <th className="px-4 py-3 font-medium">Status</th>
                                <th className="px-4 py-3 font-medium">Dato</th>
                                <th className="px-4 py-3 font-medium"></th>
                            </tr>
                        </thead>
                        <tbody>
                            {enrollments.map((enrollment) => (
                                <tr key={enrollment.id} className="border-b last:border-0">
                                    <td className="px-4 py-3">
                                        <div className="font-medium">{enrollment.student_name}</div>
                                        <div className="text-muted-foreground text-xs">{enrollment.student_email}</div>
                                    </td>
                                    <td className="px-4 py-3">{enrollment.offer_name}</td>
                                    <td className="px-4 py-3">
                                        <Badge variant="outline">
                                            {methodLabels[enrollment.payment_method] ?? enrollment.payment_method}
                                        </Badge>
                                    </td>
                                    <td className="px-4 py-3">
                                        <Badge variant="secondary">
                                            {statusLabels[enrollment.status] ?? enrollment.status}
                                        </Badge>
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground">
                                        {new Date(enrollment.created_at).toLocaleDateString('da-DK')}
                                    </td>
                                    <td className="px-4 py-3">
                                        <div className="flex items-center justify-end gap-2">
                                            {enrollment.status === 'pending_approval' && (
                                                <>
                                                    <Form {...approve.form(enrollment.id)}>
                                                        {({ processing }) => (
                                                            <Button
                                                                type="submit"
                                                                size="sm"
                                                                variant="outline"
                                                                disabled={processing}
                                                                className="gap-1.5"
                                                            >
                                                                {processing ? <Spinner /> : <CheckCircle2 className="size-4" />}
                                                                Godkend
                                                            </Button>
                                                        )}
                                                    </Form>
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        className="gap-1.5 text-destructive hover:text-destructive"
                                                        onClick={() => setRejectTarget(enrollment)}
                                                    >
                                                        <XCircle className="size-4" />
                                                        Afvis
                                                    </Button>
                                                </>
                                            )}
                                        </div>
                                    </td>
                                </tr>
                            ))}
                            {enrollments.length === 0 && (
                                <tr>
                                    <td colSpan={6} className="px-4 py-8 text-center text-muted-foreground">
                                        Ingen afventende tilmeldinger.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>

            {/* Reject Dialog */}
            <Dialog open={rejectTarget !== null} onOpenChange={(open) => !open && setRejectTarget(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Afvis tilmelding</DialogTitle>
                        <DialogDescription>
                            Angiv årsagen til afvisningen af {rejectTarget?.student_name}s tilmelding til {rejectTarget?.offer_name}.
                        </DialogDescription>
                    </DialogHeader>

                    {rejectTarget && (
                        <Form {...reject.form(rejectTarget.id)}>
                            {({ processing }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="rejection_reason">Årsag til afvisning</Label>
                                        <textarea
                                            id="rejection_reason"
                                            name="rejection_reason"
                                            rows={4}
                                            required
                                            className="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive flex min-h-[60px] w-full rounded-md border bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                                            placeholder="F.eks. pladser er fuldt booket i den ønskede periode..."
                                        />
                                    </div>

                                    <DialogFooter>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={() => setRejectTarget(null)}
                                        >
                                            Annuller
                                        </Button>
                                        <Button
                                            type="submit"
                                            variant="destructive"
                                            disabled={processing}
                                            className="gap-1.5"
                                        >
                                            {processing && <Spinner />}
                                            Afvis tilmelding
                                        </Button>
                                    </DialogFooter>
                                </>
                            )}
                        </Form>
                    )}
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
