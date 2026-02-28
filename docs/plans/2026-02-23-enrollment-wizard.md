# Enrollment Wizard Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Redesign `enroll.tsx` as a 3-step wizard — step 1 is a @schedule-x calendar where the student picks a course date, step 2 collects student info, step 3 handles payment and submits.

**Architecture:** `EnrollmentController@show` replaces `availableDates`/`courses` props with a single `courseEvents` array (id, title, start, end). `enroll.tsx` is a complete rewrite using pure React `useState` for step tracking and `router.post()` for submission — no Inertia Form component. The backend `store` endpoint is unchanged.

**Tech Stack:** Laravel 12, Pest 4, Inertia v2, React 19, Tailwind CSS v4, @schedule-x/react (already installed).

---

### Task 1: Update `EnrollmentController@show` and fix its test

The backend needs to pass `courseEvents` instead of `availableDates` + `courses`.

**Files:**
- Modify: `app/Http/Controllers/Enrollment/EnrollmentController.php`
- Modify: `tests/Feature/Enrollment/EnrollmentStoreTest.php`

**Step 1: Update the `show()` method in `app/Http/Controllers/Enrollment/EnrollmentController.php`**

Replace the entire `show()` method:

```php
public function show(Offer $offer): Response
{
    $courses = $offer->courses()->upcoming()->orderBy('start_at')->get();

    return Inertia::render('enroll', [
        'offer' => $offer,
        'courseEvents' => $courses->map(fn ($c) => [
            'id' => $c->id,
            'title' => $offer->name,
            'start' => $c->start_at->format('Y-m-d H:i'),
            'end' => $c->end_at->format('Y-m-d H:i'),
        ])->values(),
    ]);
}
```

Note: the `Course` import at the top of the file can be removed if it's no longer used elsewhere in the file (check first).

**Step 2: Update the failing test in `tests/Feature/Enrollment/EnrollmentStoreTest.php`**

Replace the last test (`enrollment show page passes available dates for the offer`) with:

```php
test('enrollment show page passes course events for the offer', function () {
    $offer = Offer::factory()->create();
    $startAt = now()->addWeek();
    Course::factory()->for($offer)->create([
        'start_at' => $startAt,
        'end_at' => $startAt->copy()->addHours(8),
    ]);
    Course::factory()->past()->for($offer)->create();

    $this->get(route('enrollment.show', $offer))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('courseEvents', 1)
            ->where('courseEvents.0.start', $startAt->format('Y-m-d H:i'))
        );
});
```

**Step 3: Run the test to verify it passes**

```bash
php artisan test --compact --filter=EnrollmentStoreTest
```

Expected: all 4 pass.

**Step 4: Run Pint**

```bash
vendor/bin/pint --dirty --format agent
```

**Step 5: Commit**

```bash
git add app/Http/Controllers/Enrollment/EnrollmentController.php \
    tests/Feature/Enrollment/EnrollmentStoreTest.php
git commit -m "feat: replace availableDates/courses props with courseEvents in enrollment show"
```

---

### Task 2: Rewrite `enroll.tsx` as 3-step wizard

**Files:**
- Modify: `resources/js/pages/enroll.tsx` (complete rewrite)

**Step 1: Replace the entire file** with the following:

```tsx
import { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import { useCalendarApp, ScheduleXCalendar } from '@schedule-x/react';
import { createViewMonthGrid } from '@schedule-x/calendar';
import '@schedule-x/theme-default/dist/index.css';
import { ArrowLeft, Banknote, Car, CheckCircle2, ChevronRight, CreditCard, Info } from 'lucide-react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import { home } from '@/routes';
import { store } from '@/actions/App/Http/Controllers/Enrollment/EnrollmentController';

interface Offer {
    id: number;
    name: string;
    description: string | null;
    price: string;
    type: 'primary' | 'addon';
    theory_lessons: number;
    driving_lessons: number;
    track_required: boolean;
    slippery_required: boolean;
}

type CourseEvent = {
    id: number;
    title: string;
    start: string;
    end: string;
};

type PaymentMethod = 'stripe' | 'cash';

const STEPS = [
    { n: 1 as const, label: 'Dato' },
    { n: 2 as const, label: 'Info' },
    { n: 3 as const, label: 'Betaling' },
];

export default function Enroll({
    offer,
    courseEvents,
}: {
    offer: Offer;
    courseEvents: CourseEvent[];
}) {
    const [step, setStep] = useState<1 | 2 | 3>(1);
    const [selectedCourse, setSelectedCourse] = useState<CourseEvent | null>(null);
    const [fields, setFields] = useState({
        name: '',
        email: '',
        phone: '',
        cpr: '',
        password: '',
        password_confirmation: '',
    });
    const [paymentMethod, setPaymentMethod] = useState<PaymentMethod>('stripe');
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [processing, setProcessing] = useState(false);

    const calendarEvents = courseEvents.map((c) => ({
        id: String(c.id),
        title: c.title,
        start: c.start,
        end: c.end,
    }));

    const calendar = useCalendarApp({
        defaultView: createViewMonthGrid().name,
        views: [createViewMonthGrid()],
        events: calendarEvents,
        callbacks: {
            onEventClick(event: { id: string }) {
                const course = courseEvents.find((c) => String(c.id) === event.id) ?? null;
                setSelectedCourse(course);
            },
        },
    });

    function handleSubmit() {
        if (!selectedCourse) { return; }
        setProcessing(true);
        router.post(
            store(offer.id).url,
            {
                course_id: selectedCourse.id,
                ...fields,
                payment_method: paymentMethod,
            },
            {
                onError: (errs) => {
                    setErrors(errs);
                    setProcessing(false);
                    if (errs.course_id) { setStep(1); }
                },
                onFinish: () => setProcessing(false),
            },
        );
    }

    return (
        <div className="min-h-screen bg-background text-foreground font-sans">
            <Head title={`Tilmeld dig – ${offer.name}`} />

            {/* Navbar */}
            <header className="sticky top-0 z-50 w-full border-b border-border/40 bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
                <div className="container mx-auto flex h-16 items-center px-4 lg:px-8">
                    <Link href={home()} className="flex items-center gap-2">
                        <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                            <Car size={20} />
                        </div>
                        <span className="text-xl font-bold tracking-tight">Køreskole Pro</span>
                    </Link>
                </div>
            </header>

            <main className="container mx-auto px-4 lg:px-8 py-12">
                <Link
                    href={home()}
                    className="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-primary transition-colors mb-8"
                >
                    <ArrowLeft size={16} />
                    Tilbage til forsiden
                </Link>

                <div className="grid lg:grid-cols-2 gap-12 max-w-5xl">
                    {/* Wizard */}
                    <div className="order-2 lg:order-1">
                        <h1 className="text-3xl font-bold tracking-tight mb-2">Tilmeld dig nu</h1>
                        <p className="text-muted-foreground mb-6">Opret din konto og kom i gang med kørekortet.</p>

                        {/* Step indicator */}
                        <div className="flex items-center gap-3 mb-8">
                            {STEPS.map(({ n, label }, i) => (
                                <div key={n} className="flex items-center gap-2">
                                    <span
                                        className={cn(
                                            'flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold',
                                            step === n && 'bg-primary text-primary-foreground',
                                            step > n && 'bg-primary/20 text-primary',
                                            step < n && 'bg-muted text-muted-foreground',
                                        )}
                                    >
                                        {step > n ? '✓' : n}
                                    </span>
                                    <span className={cn('text-sm', step === n ? 'font-semibold' : 'text-muted-foreground')}>
                                        {label}
                                    </span>
                                    {i < STEPS.length - 1 && <ChevronRight className="size-3 text-muted-foreground" />}
                                </div>
                            ))}
                        </div>

                        {/* Step 1: Calendar */}
                        {step === 1 && (
                            <div className="space-y-4">
                                <ScheduleXCalendar calendarApp={calendar} />
                                {selectedCourse ? (
                                    <div className="flex items-center gap-2 rounded-lg border border-primary/30 bg-primary/5 p-3 text-sm">
                                        <CheckCircle2 className="size-4 shrink-0 text-primary" />
                                        <span>
                                            <strong>{selectedCourse.title}</strong>
                                            {' — '}
                                            {new Date(selectedCourse.start).toLocaleString('da-DK', {
                                                dateStyle: 'long',
                                                timeStyle: 'short',
                                            })}
                                            {' – '}
                                            {new Date(selectedCourse.end).toLocaleTimeString('da-DK', {
                                                timeStyle: 'short',
                                            })}
                                        </span>
                                    </div>
                                ) : (
                                    <p className="text-sm text-muted-foreground">Klik på en dato i kalenderen for at vælge.</p>
                                )}
                                {errors.course_id && <InputError message={errors.course_id} />}
                                <Button onClick={() => setStep(2)} disabled={!selectedCourse} className="w-full">
                                    Videre →
                                </Button>
                            </div>
                        )}

                        {/* Step 2: Student info */}
                        {step === 2 && (
                            <div className="space-y-5">
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Fulde navn</Label>
                                    <Input
                                        id="name"
                                        value={fields.name}
                                        onChange={(e) => setFields({ ...fields, name: e.target.value })}
                                        required
                                        autoFocus
                                        autoComplete="name"
                                        placeholder="Dit fulde navn"
                                    />
                                    <InputError message={errors.name} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="email">E-mailadresse</Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        value={fields.email}
                                        onChange={(e) => setFields({ ...fields, email: e.target.value })}
                                        required
                                        autoComplete="email"
                                        placeholder="din@email.dk"
                                    />
                                    <InputError message={errors.email} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="phone">Telefonnummer</Label>
                                    <Input
                                        id="phone"
                                        type="tel"
                                        value={fields.phone}
                                        onChange={(e) => setFields({ ...fields, phone: e.target.value })}
                                        autoComplete="tel"
                                        placeholder="+45 12 34 56 78"
                                    />
                                    <InputError message={errors.phone} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="cpr">
                                        CPR-nummer{' '}
                                        <span className="text-muted-foreground font-normal">(valgfrit)</span>
                                    </Label>
                                    <Input
                                        id="cpr"
                                        value={fields.cpr}
                                        onChange={(e) => setFields({ ...fields, cpr: e.target.value })}
                                        placeholder="DDMMÅÅ-XXXX"
                                    />
                                    <InputError message={errors.cpr} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="password">Adgangskode</Label>
                                    <Input
                                        id="password"
                                        type="password"
                                        value={fields.password}
                                        onChange={(e) => setFields({ ...fields, password: e.target.value })}
                                        required
                                        autoComplete="new-password"
                                        placeholder="Min. 8 tegn"
                                    />
                                    <InputError message={errors.password} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="password_confirmation">Bekræft adgangskode</Label>
                                    <Input
                                        id="password_confirmation"
                                        type="password"
                                        value={fields.password_confirmation}
                                        onChange={(e) => setFields({ ...fields, password_confirmation: e.target.value })}
                                        required
                                        autoComplete="new-password"
                                        placeholder="Gentag adgangskode"
                                    />
                                    <InputError message={errors.password_confirmation} />
                                </div>
                                <div className="flex gap-3">
                                    <Button variant="outline" onClick={() => setStep(1)} className="flex-1">
                                        ← Tilbage
                                    </Button>
                                    <Button onClick={() => setStep(3)} className="flex-1">
                                        Videre →
                                    </Button>
                                </div>
                            </div>
                        )}

                        {/* Step 3: Payment + submit */}
                        {step === 3 && (
                            <div className="space-y-5">
                                {/* Selected course summary */}
                                {selectedCourse && (
                                    <div className="rounded-lg border bg-muted/30 p-4 text-sm">
                                        <p className="font-medium">{selectedCourse.title}</p>
                                        <p className="text-muted-foreground mt-0.5">
                                            {new Date(selectedCourse.start).toLocaleString('da-DK', {
                                                dateStyle: 'long',
                                                timeStyle: 'short',
                                            })}
                                            {' – '}
                                            {new Date(selectedCourse.end).toLocaleTimeString('da-DK', {
                                                timeStyle: 'short',
                                            })}
                                        </p>
                                    </div>
                                )}

                                <div className="grid gap-2">
                                    <Label>Betalingsmetode</Label>
                                    <div className="grid grid-cols-2 gap-3">
                                        <button
                                            type="button"
                                            onClick={() => setPaymentMethod('stripe')}
                                            className={[
                                                'flex flex-col items-start gap-1.5 rounded-xl border p-4 text-left transition-colors',
                                                paymentMethod === 'stripe'
                                                    ? 'border-primary bg-primary/5'
                                                    : 'border-border hover:border-muted-foreground/50',
                                            ].join(' ')}
                                        >
                                            <CreditCard className="size-5 text-primary" />
                                            <span className="font-medium text-sm">Kortbetaling</span>
                                            <span className="text-xs text-muted-foreground">Via Stripe Checkout</span>
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => setPaymentMethod('cash')}
                                            className={[
                                                'flex flex-col items-start gap-1.5 rounded-xl border p-4 text-left transition-colors',
                                                paymentMethod === 'cash'
                                                    ? 'border-primary bg-primary/5'
                                                    : 'border-border hover:border-muted-foreground/50',
                                            ].join(' ')}
                                        >
                                            <Banknote className="size-5 text-primary" />
                                            <span className="font-medium text-sm">Kontant</span>
                                            <span className="text-xs text-muted-foreground">Betales ved fremmøde</span>
                                        </button>
                                    </div>
                                    <InputError message={errors.payment_method} />

                                    {paymentMethod === 'stripe' && (
                                        <div className="flex items-start gap-2 rounded-lg border bg-muted/50 p-3 text-sm text-muted-foreground">
                                            <Info className="mt-0.5 size-4 shrink-0" />
                                            <span>Du vil blive videresendt til Stripe Checkout for sikker betaling.</span>
                                        </div>
                                    )}
                                    {paymentMethod === 'cash' && (
                                        <div className="flex items-start gap-2 rounded-lg border bg-muted/50 p-3 text-sm text-muted-foreground">
                                            <Info className="mt-0.5 size-4 shrink-0" />
                                            <span>Din tilmelding afventer godkendelse fra en instruktør, inden den aktiveres.</span>
                                        </div>
                                    )}
                                </div>

                                <div className="flex gap-3">
                                    <Button variant="outline" onClick={() => setStep(2)} disabled={processing} className="flex-1">
                                        ← Tilbage
                                    </Button>
                                    <Button onClick={handleSubmit} disabled={processing} className="flex-1 h-12 text-base">
                                        {processing && <Spinner />}
                                        {paymentMethod === 'stripe' ? 'Gå til betaling' : 'Opret konto og tilmeld'}
                                    </Button>
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Offer Card */}
                    <div className="order-1 lg:order-2">
                        <div className="sticky top-24 rounded-3xl border border-border bg-card p-8 shadow-sm">
                            {offer.type === 'primary' && (
                                <div className="inline-flex items-center rounded-full bg-primary px-4 py-1 text-sm font-semibold text-primary-foreground mb-6">
                                    Mest populære
                                </div>
                            )}
                            <h2 className="text-2xl font-bold">{offer.name}</h2>
                            {offer.description && (
                                <p className="mt-2 text-muted-foreground">{offer.description}</p>
                            )}
                            <div className="mt-6 flex items-baseline text-5xl font-extrabold">
                                {Number(offer.price).toLocaleString('da-DK')}
                                <span className="ml-1 text-xl font-medium text-muted-foreground">DKK</span>
                            </div>
                            <ul className="mt-8 space-y-4">
                                {offer.theory_lessons > 0 && (
                                    <li className="flex items-center gap-3">
                                        <CheckCircle2 className="h-5 w-5 shrink-0 text-primary" />
                                        <span>{offer.theory_lessons} Teoritimer</span>
                                    </li>
                                )}
                                {offer.driving_lessons > 0 && (
                                    <li className="flex items-center gap-3">
                                        <CheckCircle2 className="h-5 w-5 shrink-0 text-primary" />
                                        <span>{offer.driving_lessons} Kørelektioner (45 min)</span>
                                    </li>
                                )}
                                {offer.track_required && (
                                    <li className="flex items-center gap-3">
                                        <CheckCircle2 className="h-5 w-5 shrink-0 text-primary" />
                                        <span>Manøvrebane</span>
                                    </li>
                                )}
                                {offer.slippery_required && (
                                    <li className="flex items-center gap-3">
                                        <CheckCircle2 className="h-5 w-5 shrink-0 text-primary" />
                                        <span>Køreteknisk anlæg (Glatbane)</span>
                                    </li>
                                )}
                            </ul>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    );
}
```

**Step 2: Build to check for TypeScript errors**

```bash
npm run build 2>&1 | tail -30
```

Expected: no TypeScript errors. Fix any before continuing.

**Step 3: Commit**

```bash
git add resources/js/pages/enroll.tsx
git commit -m "feat: redesign enrollment as 3-step wizard with @schedule-x calendar"
```

---

### Task 3: Pint + full test suite

**Step 1: Run Pint**

```bash
vendor/bin/pint --dirty --format agent
```

**Step 2: Run all tests**

```bash
php artisan test --compact
```

Expected: all green. Fix any failures before declaring done.

**Step 3: Commit if Pint made changes**

```bash
git add -p
git commit -m "style: apply pint formatting"
```
