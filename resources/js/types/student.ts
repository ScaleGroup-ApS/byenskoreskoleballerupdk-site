import type { User } from './auth';

export type Student = {
    id: number;
    user_id: number;
    user: User;
    phone: string | null;
    cpr: string | null;
    status: StudentStatus;
    start_date: string | null;
    created_at: string;
    updated_at: string;
};

export type StudentStatus = 'active' | 'inactive' | 'graduated' | 'dropped_out';

export type PaginatedStudents = {
    data: Student[];
    links: PaginationLinks;
    meta: PaginationMeta;
};

type PaginationLinks = {
    first: string | null;
    last: string | null;
    prev: string | null;
    next: string | null;
};

type PaginationMeta = {
    current_page: number;
    from: number | null;
    last_page: number;
    per_page: number;
    to: number | null;
    total: number;
};
