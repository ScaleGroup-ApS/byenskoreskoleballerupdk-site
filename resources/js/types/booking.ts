export type BookingEvent = {
    id: number;
    title: string;
    start: string;
    end: string;
    type: BookingType;
    status: BookingStatus;
    instructor: string;
    vehicle: string | null;
    notes: string | null;
};

export type BookingType =
    | 'driving_lesson'
    | 'theory_lesson'
    | 'track_driving'
    | 'slippery_driving'
    | 'exam';

export type BookingStatus = 'scheduled' | 'completed' | 'cancelled' | 'no_show';

export const bookingTypeColors: Record<BookingType, string> = {
    driving_lesson: '#3b82f6',
    theory_lesson: '#22c55e',
    track_driving: '#f97316',
    slippery_driving: '#a855f7',
    exam: '#ef4444',
};

export const bookingTypeLabels: Record<BookingType, string> = {
    driving_lesson: 'Køretime',
    theory_lesson: 'Teorilektion',
    track_driving: 'Banekørsel',
    slippery_driving: 'Glat bane',
    exam: 'Eksamen',
};
