import { Head } from '@inertiajs/react';
import { BookOpen, Calendar, CreditCard, GraduationCap, HelpCircle } from 'lucide-react';
import Heading from '@/components/heading';
import AppLayout from '@/layouts/app-layout';
import { index } from '@/routes/timeline';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Hændelseslog', href: index().url }];

// Add new event labels here — no backend changes needed.
const eventLabels: Record<string, string> = {
    StudentEnrolled: 'Elev oprettet',
    StudentStatusChanged: 'Status ændret',
    StudentDeleted: 'Elev slettet',
    StudentLoginLinkSent: 'Login-link sendt',
    BookingCreated: 'Booking oprettet',
    BookingUpdated: 'Booking opdateret',
    BookingCompleted: 'Booking gennemført',
    BookingCancelled: 'Booking aflyst',
    BookingNoShow: 'Udeblivelse',
    OfferAssigned: 'Tilbud tildelt',
    PaymentRecorded: 'Betaling registreret',
    EnrollmentRequested: 'Tilmelding indsendt',
    EnrollmentApproved: 'Tilmelding godkendt',
    EnrollmentRejected: 'Tilmelding afvist',
    StripePaymentCompleted: 'Stripe-betaling gennemført',
};

type TimelineEvent = {
    id: number;
    event_type: string;
    category: 'student' | 'booking' | 'payment' | 'enrollment' | 'other';
    data: Record<string, unknown>;
    occurred_at: string;
};

type PaginatedEvents = {
    data: TimelineEvent[];
    from: number | null;
    to: number | null;
    total: number;
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

const categoryIcons: Record<string, React.ElementType> = {
    student: GraduationCap,
    booking: Calendar,
    payment: CreditCard,
    enrollment: BookOpen,
    other: HelpCircle,
};

const categoryColors: Record<string, string> = {
    student: 'bg-blue-100 text-blue-600',
    booking: 'bg-purple-100 text-purple-600',
    payment: 'bg-green-100 text-green-600',
    enrollment: 'bg-orange-100 text-orange-600',
    other: 'bg-gray-100 text-gray-600',
};

function formatData(data: Record<string, unknown>): string {
    const parts: string[] = [];

    if (data.student_name) {
        parts.push(String(data.student_name));
    }
    if (data.offer_name) {
        parts.push(String(data.offer_name));
    }
    if (data.amount) {
        parts.push(`${Number(data.amount).toLocaleString('da-DK')} kr.`);
    }
    if (data.type) {
        parts.push(String(data.type));
    }
    if (data.new_status) {
        parts.push(`→ ${String(data.new_status)}`);
    }
    if (data.starts_at && !data.new_status) {
        parts.push(new Date(String(data.starts_at)).toLocaleDateString('da-DK', { dateStyle: 'medium' }));
    }
    if (data.reason) {
        parts.push(`Årsag: ${String(data.reason)}`);
    }
    if (data.rejection_reason) {
        parts.push(`Årsag: ${String(data.rejection_reason)}`);
    }

    return parts.join(' · ');
}

export default function TimelineIndex({ events }: { events: PaginatedEvents }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Hændelseslog" />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <Heading title="Hændelseslog" description={`${events.total} hændelser registreret`} />
                </div>

                {events.data.length === 0 ? (
                    <div className="rounded-xl border px-4 py-16 text-center text-muted-foreground">
                        Ingen hændelser fundet.
                    </div>
                ) : (
                    <div className="relative">
                        <div className="absolute left-6 top-0 h-full w-px bg-border" />

                        <ul className="space-y-4">
                            {events.data.map((event) => {
                                const Icon = categoryIcons[event.category] ?? HelpCircle;
                                const iconClass = categoryColors[event.category] ?? categoryColors.other;
                                const label = eventLabels[event.event_type] ?? event.event_type;
                                const summary = formatData(event.data);

                                return (
                                    <li key={event.id} className="relative flex gap-4 pl-14">
                                        <span
                                            className={`absolute left-3 flex size-6 shrink-0 items-center justify-center rounded-full ${iconClass}`}
                                        >
                                            <Icon className="size-3.5" />
                                        </span>

                                        <div className="flex min-w-0 flex-1 flex-col rounded-xl border bg-card px-4 py-3">
                                            <div className="flex items-start justify-between gap-2">
                                                <span className="text-sm font-medium">{label}</span>
                                                <time
                                                    className="shrink-0 text-xs text-muted-foreground"
                                                    dateTime={event.occurred_at}
                                                >
                                                    {new Date(event.occurred_at).toLocaleString('da-DK', {
                                                        dateStyle: 'short',
                                                        timeStyle: 'short',
                                                    })}
                                                </time>
                                            </div>
                                            {summary && (
                                                <span className="mt-0.5 text-xs text-muted-foreground">{summary}</span>
                                            )}
                                        </div>
                                    </li>
                                );
                            })}
                        </ul>
                    </div>
                )}

                {events.last_page > 1 && (
                    <div className="flex items-center justify-between text-sm text-muted-foreground">
                        <span>
                            {events.from}–{events.to} af {events.total}
                        </span>
                        <div className="flex gap-2">
                            {events.prev_page_url && (
                                <a href={events.prev_page_url} className="rounded-md border px-3 py-1 hover:bg-muted">
                                    Forrige
                                </a>
                            )}
                            {events.next_page_url && (
                                <a href={events.next_page_url} className="rounded-md border px-3 py-1 hover:bg-muted">
                                    Næste
                                </a>
                            )}
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
