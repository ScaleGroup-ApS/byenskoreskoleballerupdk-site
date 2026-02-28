import { Head } from '@inertiajs/react';
import { CheckCircle, XCircle } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/app-layout';
import { index as studentsIndex, show as studentShow } from '@/routes/students';
import type { BreadcrumbItem } from '@/types';

const typeLabels: Record<string, string> = {
    driving_lesson: 'Køretimer',
    theory_lesson: 'Teorilektioner',
    track_driving: 'Banekørsel',
    slippery_driving: 'Glat bane',
};

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

type Student = {
    id: number;
    user: { name: string };
};

export default function ProgressionShow({
    student,
    readiness,
    balance,
}: {
    student: Student;
    readiness: Readiness;
    balance: Balance;
}) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Elever', href: studentsIndex().url },
        { title: student.user.name, href: studentShow(student).url },
        { title: 'Fremgang', href: '#' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fremgang — ${student.user.name}`} />

            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title={`Fremgang — ${student.user.name}`}
                        description="Oversigt over gennemførte lektioner og eksamensparathed"
                    />
                    <Badge variant={readiness.is_ready ? 'default' : 'secondary'}>
                        {readiness.is_ready ? '✓ Klar til eksamen' : 'Ikke klar endnu'}
                    </Badge>
                </div>

                {/* Lesson progress */}
                <div className="rounded-xl border">
                    <div className="border-b px-4 py-3">
                        <p className="font-medium">Lektionsfremgang</p>
                    </div>
                    <div className="divide-y">
                        {Object.entries(readiness.required)
                            .filter(([, needed]) => needed > 0)
                            .map(([type, needed]) => {
                                const done = readiness.completed[type] ?? 0;
                                const isMet = done >= needed;

                                return (
                                    <div key={type} className="flex items-center justify-between px-4 py-3">
                                        <div className="flex items-center gap-2">
                                            {isMet
                                                ? <CheckCircle className="size-4 text-green-500" />
                                                : <XCircle className="size-4 text-muted-foreground" />
                                            }
                                            <span className="text-sm">{typeLabels[type] ?? type}</span>
                                        </div>
                                        <span className="text-sm text-muted-foreground">
                                            {done} / {needed}
                                        </span>
                                    </div>
                                );
                            })}
                        {Object.values(readiness.required).every((v) => v === 0) && (
                            <div className="px-4 py-6 text-center text-sm text-muted-foreground">
                                Ingen tilbud tildelt endnu.
                            </div>
                        )}
                    </div>
                </div>

                {/* Balance */}
                <div className="rounded-xl border">
                    <div className="border-b px-4 py-3">
                        <p className="font-medium">Saldo</p>
                    </div>
                    <div className="divide-y">
                        <div className="flex justify-between px-4 py-3 text-sm">
                            <span>Skylder i alt</span>
                            <span>{Number(balance.total_owed).toLocaleString('da-DK')} kr.</span>
                        </div>
                        <div className="flex justify-between px-4 py-3 text-sm">
                            <span>Betalt</span>
                            <span className="text-green-600">{Number(balance.total_paid).toLocaleString('da-DK')} kr.</span>
                        </div>
                        <div className="flex justify-between px-4 py-3 text-sm font-medium">
                            <span>Udestående</span>
                            <span className={balance.outstanding > 0 ? 'text-destructive' : 'text-green-600'}>
                                {Number(balance.outstanding).toLocaleString('da-DK')} kr.
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
