import { Head, router, usePoll } from '@inertiajs/react';
import { format } from 'date-fns';
import { CheckCircle, Clock, CreditCard, Download, FileText, Loader2, XCircle } from 'lucide-react';
import { useEffect, useRef } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import StudentLayout from '@/layouts/student-layout';
import { dashboard } from '@/routes/student';
import type { BreadcrumbItem } from '@/types';

type Booking = {
    type: string;
    starts_at: string;
    ends_at: string;
} | null;

type Readiness = {
    is_ready: boolean;
    completed: Record<string, number>;
    required: Record<string, number>;
    missing: Record<string, number>;
};

type Balance = {
    total_owed: number;
    total_paid: number;
    outstanding: number;
};

type PendingEnrollment = {
    status: 'pending_payment' | 'pending_approval';
    payment_method: 'stripe' | 'cash';
    offer_price: number;
} | null;

type Material = {
    id: number;
    name: string;
    file_name: string;
    mime_type: string;
    size: string;
    url: string;
    offer_name: string;
};

const bookingTypeLabels: Record<string, string> = {
    driving_lesson: 'Køretest',
    theory_lesson: 'Teoritime',
    track_driving: 'Banekørsel',
    slippery_driving: 'Glat bane',
    exam: 'Eksamen',
};

const readinessTypeLabels: Record<string, string> = {
    driving_lesson: 'Køretimer',
    theory_lesson: 'Teorilektioner',
    track_driving: 'Banekørsel',
    slippery_driving: 'Glat bane',
};

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: dashboard().url }];

export default function StudentDashboard({
    pendingEnrollment,
    booking,
    readiness,
    balance,
    materials,
}: {
    pendingEnrollment: PendingEnrollment;
    booking: Booking;
    readiness: Readiness;
    balance: Balance;
    materials: Material[];
}) {
    const wasPending = useRef(!!pendingEnrollment);
    const { stop } = usePoll(5000, { only: ['pendingEnrollment'] }, { autoStart: !!pendingEnrollment });

    useEffect(() => {
        if (wasPending.current && !pendingEnrollment) {
            stop();
            router.reload();
        }
        wasPending.current = !!pendingEnrollment;
    }, [pendingEnrollment, stop]);

    if (pendingEnrollment) {
        const isStripe = pendingEnrollment.payment_method === 'stripe';

        return (
            <div className="flex min-h-screen flex-col items-center justify-center bg-background p-6 text-center">
                <Head title={isStripe ? 'Afventer betaling' : 'Afventer godkendelse'} />
                <div className="max-w-sm space-y-6">
                    <div className="flex justify-center">
                        <div className="flex size-20 items-center justify-center rounded-full bg-muted">
                            {isStripe ? (
                                <CreditCard className="size-9 text-muted-foreground" />
                            ) : (
                                <Clock className="size-9 text-muted-foreground" />
                            )}
                        </div>
                    </div>

                    <div className="space-y-2">
                        <h1 className="text-2xl font-semibold">
                            {isStripe ? 'Afventer betaling' : 'Afventer godkendelse'}
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            {isStripe
                                ? 'Din tilmelding afventer betalingsbekræftelse fra Stripe. Kontakt os, hvis du har problemer.'
                                : 'Din tilmelding afventer godkendelse fra en instruktør. Du vil modtage besked, når den er behandlet.'}
                        </p>
                        <p className="text-lg font-medium">
                            {Number(pendingEnrollment.offer_price).toLocaleString('da-DK')} kr.
                        </p>
                    </div>

                    <div className="flex items-center justify-center gap-2 text-xs text-muted-foreground">
                        <Loader2 className="size-3 animate-spin" />
                        Checker for opdateringer…
                    </div>
                </div>
            </div>
        );
    }

    return (
        <StudentLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">

                {/* Upcoming booking */}
                <div className="space-y-3">
                    <Heading variant="small" title="Næste lektion" />
                    {booking ? (
                        <div className="rounded-xl border p-5">
                            <p className="font-medium">
                                {bookingTypeLabels[booking.type] ?? booking.type}
                            </p>
                            <p className="mt-1 text-sm text-muted-foreground">
                                {format(new Date(booking.starts_at), 'd-M-yyyy HH:mm')}
                                {' – '}
                                {format(new Date(booking.ends_at), 'HH:mm')}
                            </p>
                        </div>
                    ) : (
                        <p className="text-sm text-muted-foreground">Ingen kommende lektioner.</p>
                    )}
                </div>

                {/* Progression */}
                <div className="space-y-3">
                    <div className="flex items-center justify-between">
                        <Heading variant="small" title="Fremgang" />
                        <Badge variant={readiness.is_ready ? 'default' : 'secondary'}>
                            {readiness.is_ready ? '✓ Klar til eksamen' : 'Ikke eksemensklar endnu'}
                        </Badge>
                    </div>

                    <div className="rounded-xl border divide-y">
                        {Object.entries(readiness.required).map(([type, needed]) => {
                            const done = readiness.completed[type] ?? 0;
                            const met = done >= needed;
                            return (
                                <div key={type} className="flex items-center justify-between px-4 py-3">
                                    <div className="flex items-center gap-2">
                                        {met
                                            ? <CheckCircle className="size-4 text-green-600" />
                                            : <XCircle className="size-4 text-muted-foreground" />
                                        }
                                        <span className="text-sm">
                                            {readinessTypeLabels[type] ?? type}
                                        </span>
                                    </div>
                                    <span className="text-sm text-muted-foreground">
                                        {done} / {needed}
                                    </span>
                                </div>
                            );
                        })}
                    </div>

                    {balance.outstanding > 0 && (
                        <div className="rounded-xl border px-4 py-3 flex items-center justify-between">
                            <span className="text-sm text-muted-foreground">Udestående saldo</span>
                            <span className="text-sm font-medium">
                                {Number(balance.outstanding).toLocaleString('da-DK')} kr.
                            </span>
                        </div>
                    )}
                </div>

                {/* Course materials */}
                {materials.length > 0 && (
                    <div className="space-y-3">
                        <Heading variant="small" title="Kursusmateriale" />
                        <div className="rounded-xl border divide-y">
                            {materials.map((material) => (
                                <a
                                    key={material.id}
                                    href={material.url}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="flex items-center justify-between px-4 py-3 hover:bg-muted/50 transition-colors"
                                >
                                    <div className="flex items-center gap-2">
                                        <FileText className="size-4 shrink-0 text-muted-foreground" />
                                        <span className="text-sm">{material.name}</span>
                                    </div>
                                    <div className="flex items-center gap-3">
                                        <span className="text-xs text-muted-foreground">{material.size}</span>
                                        <Download className="size-4 text-muted-foreground" />
                                    </div>
                                </a>
                            ))}
                        </div>
                    </div>
                )}

            </div>
        </StudentLayout>
    );
}
