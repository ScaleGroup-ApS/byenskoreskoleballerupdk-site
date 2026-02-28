import { useForm } from '@inertiajs/react';
import StudentLoginLinkController from '@/actions/App/Http/Controllers/Students/StudentLoginLinkController';
import type { Student } from '@/types';

export function useLoginLink(student: Student) {
    const form = useForm({});

    function sendLoginLink() {
        form.submit(StudentLoginLinkController(student));
    }

    return { sendLoginLink, processing: form.processing };
}
