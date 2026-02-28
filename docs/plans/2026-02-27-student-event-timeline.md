# Student Event Timeline Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add a per-student Verbs event timeline section to the student show page, visible to admins only.

**Architecture:** Query `verb_events` by `data->student_id` JSON path (covers all 12 event types). Map events to Danish summaries in the controller via private helper methods. Render a vertical timeline UI in the existing show page.

**Tech Stack:** Laravel 12, Verbs (`hirethunk/verbs` v0.7.1), Inertia v2, React 19, Tailwind CSS v4, Pest 4.

---

### Task 1: Write failing test for `eventTimeline` prop in `StudentController::show`

**Files:**
- Modify: `tests/Feature/Students/StudentControllerTest.php`

**Step 1: Add tests to `StudentControllerTest.php`**

Append to the end of the file:

```php
test('admin sees eventTimeline prop on student show page', function () {
    $admin = User::factory()->create();
    $student = Student::factory()->create();

    \App\Events\StudentEnrolled::fire(
        student_id: $student->id,
        student_name: $student->user->name,
        start_date: $student->start_date->toDateString(),
    );

    \Thunk\Verbs\Facades\Verbs::commit();

    $this->actingAs($admin)
        ->get(route('students.show', $student))
        ->assertInertia(fn ($page) => $page
            ->has('eventTimeline', 1)
            ->where('eventTimeline.0.summary', 'Elev tilmeldt')
            ->where('eventTimeline.0.category', 'student')
            ->has('eventTimeline.0.id')
            ->has('eventTimeline.0.created_at')
        );
});

test('non-admin sees empty eventTimeline on student show page', function () {
    $instructor = User::factory()->instructor()->create();
    $student = Student::factory()->create();

    \App\Events\StudentEnrolled::fire(
        student_id: $student->id,
        student_name: $student->user->name,
        start_date: $student->start_date->toDateString(),
    );

    \Thunk\Verbs\Facades\Verbs::commit();

    $this->actingAs($instructor)
        ->get(route('students.show', $student))
        ->assertInertia(fn ($page) => $page
            ->has('eventTimeline', 0)
        );
});
```

**Step 2: Run tests to verify they fail**

```bash
php artisan test --compact --filter="admin sees eventTimeline"
```

Expected: FAIL — `eventTimeline` prop not found on page.

---

### Task 2: Implement `StudentController::show` backend changes

**Files:**
- Modify: `app/Http/Controllers/Students/StudentController.php`

**Step 1: Add `VerbEvent` import**

Add to the `use` block at the top of the file:

```php
use Thunk\Verbs\Models\VerbEvent;
```

**Step 2: Refactor `show()` to extract `$canEdit` and add `eventTimeline`**

Replace the existing `show()` method body:

```php
public function show(Request $request, Student $student): Response
{
    $this->authorize('view', $student);

    $student->load('user', 'media');

    $canEdit = $request->user()->isAdmin();

    return Inertia::render('students/show', [
        'student' => $student,
        'canEdit' => $canEdit,
        'eventTimeline' => $canEdit
            ? VerbEvent::query()
                ->where('data->student_id', $student->id)
                ->latest()
                ->get()
                ->map(fn (VerbEvent $event) => [
                    'id' => (string) $event->id,
                    'summary' => $this->eventSummary($event),
                    'category' => $this->eventCategory($event),
                    'created_at' => $event->created_at->toISOString(),
                ])
                ->all()
            : [],
    ]);
}
```

**Step 3: Add private helper methods to `StudentController`**

Add these two private methods at the end of the class (before the closing `}`):

```php
private function eventSummary(VerbEvent $event): string
{
    return match (class_basename($event->type)) {
        'BookingCreated' => 'Booking oprettet',
        'BookingCompleted' => 'Booking gennemført',
        'BookingCancelled' => 'Booking annulleret',
        'BookingNoShow' => 'Elev mødte ikke op',
        'EnrollmentRequested' => 'Tilmelding anmodet',
        'EnrollmentApproved' => 'Tilmelding godkendt',
        'EnrollmentRejected' => 'Tilmelding afvist',
        'StudentEnrolled' => 'Elev tilmeldt',
        'StudentStatusChanged' => 'Status ændret → '.($event->data['new_status'] ?? ''),
        'OfferAssigned' => 'Tilbud tildelt: '.($event->data['offer_name'] ?? ''),
        'PaymentRecorded' => 'Betaling registreret: '.number_format((float) ($event->data['amount'] ?? 0), 2, ',', '.').' kr.',
        'StripePaymentCompleted' => 'Stripe-betaling gennemført',
        default => class_basename($event->type),
    };
}

private function eventCategory(VerbEvent $event): string
{
    return match (class_basename($event->type)) {
        'BookingCreated', 'BookingCompleted', 'BookingCancelled', 'BookingNoShow' => 'booking',
        'EnrollmentRequested', 'EnrollmentApproved', 'EnrollmentRejected' => 'enrollment',
        'StudentEnrolled', 'StudentStatusChanged' => 'student',
        'OfferAssigned', 'PaymentRecorded', 'StripePaymentCompleted' => 'payment',
        default => 'other',
    };
}
```

**Step 4: Run tests to verify they pass**

```bash
php artisan test --compact --filter="eventTimeline"
```

Expected: PASS (both tests green).

**Step 5: Run Pint**

```bash
vendor/bin/pint app/Http/Controllers/Students/StudentController.php --format agent
```

**Step 6: Commit**

```bash
git add app/Http/Controllers/Students/StudentController.php tests/Feature/Students/StudentControllerTest.php
git commit -m "feat: add event timeline to student show page (backend)"
```

---

### Task 3: Add timeline section to `resources/js/pages/students/show.tsx`

**Files:**
- Modify: `resources/js/pages/students/show.tsx`

**Step 1: Add `EventTimelineEntry` type and category color map**

After the existing `MediaItem` type definition (around line 14), add:

```tsx
type EventTimelineEntry = {
    id: string;
    summary: string;
    category: 'booking' | 'enrollment' | 'student' | 'payment' | 'other';
    created_at: string;
};

const categoryDotColors: Record<string, string> = {
    booking: 'bg-blue-500',
    enrollment: 'bg-purple-500',
    student: 'bg-green-500',
    payment: 'bg-amber-500',
    other: 'bg-muted-foreground',
};
```

**Step 2: Add `eventTimeline` to the component props**

Change the component signature from:

```tsx
export default function StudentShow({ student, canEdit }: { student: Student & { media: MediaItem[] }; canEdit: boolean }) {
```

To:

```tsx
export default function StudentShow({
    student,
    canEdit,
    eventTimeline = [],
}: {
    student: Student & { media: MediaItem[] };
    canEdit: boolean;
    eventTimeline: EventTimelineEntry[];
}) {
```

**Step 3: Add the "Hændelseslog" section**

After the closing `</div>` of the documents (`max-w-lg space-y-4`) section, add:

```tsx
{canEdit && (
    <div className="max-w-lg space-y-4">
        <Heading variant="small" title="Hændelseslog" />
        {eventTimeline.length > 0 ? (
            <div className="relative border-l pl-6">
                {eventTimeline.map((entry) => (
                    <div key={entry.id} className="relative mb-4 last:mb-0">
                        <span
                            className={`absolute -left-6 top-1 size-3 -translate-x-1/2 rounded-full ${categoryDotColors[entry.category] ?? 'bg-muted-foreground'}`}
                        />
                        <p className="text-sm">{entry.summary}</p>
                        <p className="text-xs text-muted-foreground">
                            {new Date(entry.created_at).toLocaleString('da-DK')}
                        </p>
                    </div>
                ))}
            </div>
        ) : (
            <p className="text-sm text-muted-foreground">Ingen hændelser registreret.</p>
        )}
    </div>
)}
```

**Step 4: Commit**

```bash
git add resources/js/pages/students/show.tsx
git commit -m "feat: render event timeline section on student show page"
```
