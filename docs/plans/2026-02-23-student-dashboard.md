# Student Dashboard Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Create a dedicated `/student` dashboard showing the next booking and a progression summary, and redirect students away from the staff `/dashboard`.

**Architecture:** New invokable `StudentDashboardController` at `GET /student`, protected by the existing `role:student` middleware alias. `DashboardController` gains one early redirect for students. The frontend is a new `resources/js/pages/student/index.tsx` page.

**Tech Stack:** Laravel 12, Inertia v2, React 19, Tailwind CSS v4, Pest 4. Wayfinder generates typed route helpers — run `php artisan wayfinder:generate` after adding new named routes.

---

### Task 1: Register route + write failing tests

**Files:**
- Modify: `routes/web.php`
- Create: `tests/Feature/Student/StudentDashboardTest.php`

**Step 1: Add the route to `routes/web.php`**

Inside the existing `Route::middleware(['auth', 'verified'])->group(...)` block, add:

```php
use App\Http\Controllers\Student\StudentDashboardController;

Route::get('student', StudentDashboardController::class)
    ->middleware('role:student')
    ->name('student.dashboard');
```

**Step 2: Create the test file**

```bash
php artisan make:test --pest Student/StudentDashboardTest
```

**Step 3: Write the failing tests**

Replace the generated file contents with:

```php
<?php

use App\Models\Booking;
use App\Models\Student;
use App\Models\User;

test('student visiting /dashboard is redirected to student dashboard', function () {
    $user = User::factory()->student()->create();
    Student::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('student.dashboard'));
});

test('student can visit their dashboard', function () {
    $user = User::factory()->student()->create();
    Student::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('student.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('student/index')
            ->has('booking')
            ->has('readiness')
            ->has('balance')
        );
});

test('admin cannot visit student dashboard', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('student.dashboard'))
        ->assertForbidden();
});

test('instructor cannot visit student dashboard', function () {
    $instructor = User::factory()->instructor()->create();

    $this->actingAs($instructor)
        ->get(route('student.dashboard'))
        ->assertForbidden();
});

test('student dashboard shows next upcoming booking', function () {
    $user = User::factory()->student()->create();
    $student = Student::factory()->for($user)->create();
    Booking::factory()->for($student)->create([
        'starts_at' => now()->addDays(2),
        'ends_at' => now()->addDays(2)->addHours(1),
    ]);

    $this->actingAs($user)
        ->get(route('student.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->whereNot('booking', null)
        );
});

test('student dashboard booking is null when no upcoming bookings', function () {
    $user = User::factory()->student()->create();
    Student::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('student.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('booking', null)
        );
});
```

**Step 4: Run to confirm failure**

```bash
php artisan test --compact --filter=StudentDashboard
```

Expected: all tests fail — controller class does not exist yet.

---

### Task 2: Create `StudentDashboardController`

**Files:**
- Create: `app/Http/Controllers/Student/StudentDashboardController.php`

**Step 1: Generate the controller**

```bash
php artisan make:controller Student/StudentDashboardController --invokable --no-interaction
```

**Step 2: Implement `__invoke`**

```php
<?php

namespace App\Http\Controllers\Student;

use App\Actions\Payments\CalculateBalance;
use App\Actions\Progression\CheckExamReadiness;
use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentDashboardController extends Controller
{
    public function __invoke(Request $request, CheckExamReadiness $readiness, CalculateBalance $balance): Response
    {
        $student = $request->user()->student;

        abort_unless($student, 404);

        $student->load('offers');

        $booking = Booking::query()
            ->where('student_id', $student->id)
            ->where('starts_at', '>=', now())
            ->whereNotIn('status', [BookingStatus::Cancelled->value, BookingStatus::NoShow->value])
            ->orderBy('starts_at')
            ->first();

        return Inertia::render('student/index', [
            'booking' => $booking ? [
                'type' => $booking->type->value,
                'starts_at' => $booking->starts_at->toIso8601String(),
                'ends_at' => $booking->ends_at->toIso8601String(),
            ] : null,
            'readiness' => $readiness->handle($student),
            'balance' => $balance->handle($student),
        ]);
    }
}
```

**Step 3: Run tests**

```bash
php artisan test --compact --filter=StudentDashboard
```

Expected: 4 tests pass (`admin cannot visit`, `instructor cannot visit`, `booking is null`, `next upcoming booking`). The two dashboard redirect tests still fail — that's correct, we haven't added the redirect yet.

---

### Task 3: Redirect students in `DashboardController`

**Files:**
- Modify: `app/Http/Controllers/DashboardController.php`

**Step 1: Add early redirect**

At the very start of `__invoke`, before any existing logic, add:

```php
if ($user->isStudent()) {
    return redirect()->route('student.dashboard');
}
```

The `$user` variable is already the second line of the method (`$user = $request->user()`), so insert the redirect after that line.

**Step 2: Run tests**

```bash
php artisan test --compact --filter=StudentDashboard
```

Expected: all 6 tests pass.

**Step 3: Commit**

```bash
git add app/Http/Controllers/Student/StudentDashboardController.php \
        app/Http/Controllers/DashboardController.php \
        routes/web.php \
        tests/Feature/Student/StudentDashboardTest.php
git commit -m "feat: add student dashboard at /student with redirect from /dashboard"
```

---

### Task 4: Generate Wayfinder route helper + create frontend page

**Files:**
- Create: `resources/js/pages/student/index.tsx`

**Step 1: Regenerate Wayfinder so the `student.dashboard` route is available**

```bash
php artisan wayfinder:generate
```

This creates/updates `resources/js/routes/student.ts` (or similar) with a typed `dashboard()` helper.

**Step 2: Create `resources/js/pages/student/index.tsx`**

```tsx
import { Head } from '@inertiajs/react';
import { format } from 'date-fns';
import { da } from 'date-fns/locale';
import { CheckCircle, XCircle } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { dashboard } from '@/routes/student';

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
    booking,
    readiness,
    balance,
}: {
    booking: Booking;
    readiness: Readiness;
    balance: Balance;
}) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
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
                                {format(new Date(booking.starts_at), 'PPPp', { locale: da })}
                                {' – '}
                                {format(new Date(booking.ends_at), 'p', { locale: da })}
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
                            {readiness.is_ready ? '✓ Klar til eksamen' : 'Ikke klar endnu'}
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

            </div>
        </AppLayout>
    );
}
```

**Step 3: Check the exact Wayfinder import path**

After running `php artisan wayfinder:generate`, verify the generated file path:
```bash
ls resources/js/routes/
```
Adjust the import `from '@/routes/student'` to match whatever filename Wayfinder generated.

**Step 4: Commit**

```bash
git add resources/js/pages/student/index.tsx
git commit -m "feat: student dashboard frontend page"
```

---

### Task 5: Run Pint + full test suite

**Step 1: Pint**

```bash
vendor/bin/pint --dirty --format agent
```

**Step 2: Run affected tests**

```bash
php artisan test --compact --filter=StudentDashboard
php artisan test --compact --filter=Dashboard
```

Expected: all Student and Dashboard tests pass.

**Step 3: Commit any Pint fixes**

```bash
git add -p
git commit -m "style: pint formatting"
```
