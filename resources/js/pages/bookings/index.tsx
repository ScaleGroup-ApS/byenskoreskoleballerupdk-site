import type { EventClickArg } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import type { EventDropArg, EventResizeDoneArg } from '@fullcalendar/interaction';
import interactionPlugin from '@fullcalendar/interaction';
import FullCalendar from '@fullcalendar/react';
import timeGridPlugin from '@fullcalendar/timegrid';
import { Head, Link, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { index, create, update, destroy } from '@/routes/bookings';
import type { BreadcrumbItem } from '@/types';
import type { BookingEvent } from '@/types/booking';
import { bookingTypeColors, bookingTypeLabels } from '@/types/booking';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Bookinger', href: index().url },
];

export default function BookingsIndex({ bookings }: { bookings: BookingEvent[] }) {
    const [selected, setSelected] = useState<BookingEvent | null>(null);

    const events = bookings.map((b) => ({
        id: String(b.id),
        title: b.title,
        start: b.start,
        end: b.end,
        backgroundColor: bookingTypeColors[b.type],
        borderColor: bookingTypeColors[b.type],
        opacity: b.status === 'cancelled' ? 0.4 : 1,
        extendedProps: b,
    }));

    function handleDrop(info: EventDropArg) {
        const booking = info.event.extendedProps as BookingEvent;
        router.patch(update(booking).url, {
            starts_at: info.event.startStr,
            ends_at: info.event.endStr,
        }, {
            onError: () => info.revert(),
        });
    }

    function handleResize(info: EventResizeDoneArg) {
        const booking = info.event.extendedProps as BookingEvent;
        router.patch(update(booking).url, {
            starts_at: info.event.startStr,
            ends_at: info.event.endStr,
        }, {
            onError: () => info.revert(),
        });
    }

    function handleEventClick(info: EventClickArg) {
        setSelected(info.event.extendedProps as BookingEvent);
    }

    function handleCancel(booking: BookingEvent) {
        if (confirm('Er du sikker på, at du vil annullere denne booking?')) {
            router.delete(destroy(booking).url);
            setSelected(null);
        }
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Bookinger" />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <Heading title="Bookinger" description="Administrer køretimer og lektioner" />
                    <Button asChild>
                        <Link href={create().url}>
                            <Plus className="mr-2 size-4" />
                            Opret booking
                        </Link>
                    </Button>
                </div>

                {/* Legend */}
                <div className="flex flex-wrap gap-3 text-sm">
                    {(Object.entries(bookingTypeLabels) as [keyof typeof bookingTypeLabels, string][]).map(([type, label]) => (
                        <span key={type} className="flex items-center gap-1.5">
                            <span
                                className="inline-block size-3 rounded-sm"
                                style={{ backgroundColor: bookingTypeColors[type] }}
                            />
                            {label}
                        </span>
                    ))}
                </div>

                <div className="rounded-xl border p-4">
                    <FullCalendar
                        plugins={[dayGridPlugin, timeGridPlugin, interactionPlugin]}
                        initialView="timeGridWeek"
                        headerToolbar={{
                            left: 'prev,next today',
                            center: 'title',
                            right: 'dayGridMonth,timeGridWeek,timeGridDay',
                        }}
                        locale="da"
                        firstDay={1}
                        slotMinTime="07:00:00"
                        slotMaxTime="20:00:00"
                        height="auto"
                        editable
                        events={events}
                        eventDrop={handleDrop}
                        eventResize={handleResize}
                        eventClick={handleEventClick}
                    />
                </div>

                {/* Detail panel */}
                {selected && (
                    <div className="rounded-xl border p-4">
                        <div className="flex items-start justify-between">
                            <div className="space-y-1">
                                <p className="font-medium">{selected.title}</p>
                                <p className="text-sm text-muted-foreground">
                                    {bookingTypeLabels[selected.type]} · {selected.instructor}
                                    {selected.vehicle && ` · ${selected.vehicle}`}
                                </p>
                                {selected.notes && (
                                    <p className="text-sm text-muted-foreground">{selected.notes}</p>
                                )}
                            </div>
                            <div className="flex gap-2">
                                {selected.status === 'scheduled' && (
                                    <>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => {
                                                router.patch(update(selected).url, { status: 'completed' });
                                                setSelected(null);
                                            }}
                                        >
                                            Gennemført
                                        </Button>
                                        <Button
                                            variant="destructive"
                                            size="sm"
                                            onClick={() => handleCancel(selected)}
                                        >
                                            Annuller
                                        </Button>
                                    </>
                                )}
                                <Button variant="ghost" size="sm" onClick={() => setSelected(null)}>
                                    ✕
                                </Button>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
