# Course Admin Pages Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add `start_at`/`end_at` datetimes to courses, a `/courses` calendar index (month + week views via @schedule-x), and a `/courses/{id}` detail page with enrolled students and an edit form.

**Architecture:** `start_date DATE` is replaced with `start_at DATETIME` + `end_at DATETIME`. A new top-level `Courses\CourseController` (index/show/update) is added alongside the existing `Offers\CourseController` (store/destroy). Two new Inertia pages — `courses/index.tsx` (calendar) and `courses/show.tsx` (detail). The sidebar gains a "Kurser" entry.

**Tech Stack:** Laravel 12, Pest 4, Inertia v2, React 19, Tailwind CSS v4, @schedule-x/react + @schedule-x/calendar + @schedule-x/theme-default, Wayfinder.

---

### Task 1: Migrate `courses` table to `start_at`/`end_at` + update model and factory

**Files:**
- Create: `database/migrations/YYYY_MM_DD_HHMMSS_update_courses_datetime.php`
- Modify: `app/Models/Course.php`
- Modify: `database/factories/CourseFactory.php`
- Modify: `tests/Feature/Offers/CourseTest.php`

**Step 1: Generate the migration**

```bash
php artisan make:migration update_courses_start_at_end_at --no-interaction
```

Write the generated migration:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('start_date');
            $table->dateTime('start_at')->after('offer_id');
            $table->dateTime('end_at')->after('start_at');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['start_at', 'end_at']);
            $table->date('start_date')->after('offer_id');
        });
    }
};
```

**Step 2: Run the migration**

```bash
php artisan migrate --no-interaction
```

Expected: `courses` table now has `start_at` and `end_at` columns, `start_date` is gone.

**Step 3: Update `app/Models/Course.php`**

Replace the entire file:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    /** @use HasFactory<\Database\Factories\CourseFactory> */
    use HasFactory;

    protected $fillable = [
        'offer_id',
        'start_at',
        'end_at',
        'max_students',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'max_students' => 'integer',
        ];
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /** @param Builder<Course> $query */
    public function scopeUpcoming(Builder $query): void
    {
        $query->where('start_at', '>=', now());
    }
}
```

**Step 4: Update `database/factories/CourseFactory.php`**

Replace the entire file:

```php
<?php

namespace Database\Factories;

use App\Models\Offer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Course>
 */
class CourseFactory extends Factory
{
    public function definition(): array
    {
        $startAt = fake()->dateTimeBetween('now', '+6 months');

        return [
            'offer_id' => Offer::factory(),
            'start_at' => $startAt->format('Y-m-d H:i:s'),
            'end_at' => (clone $startAt)->modify('+8 hours')->format('Y-m-d H:i:s'),
            'max_students' => null,
        ];
    }

    public function past(): static
    {
        return $this->state(function () {
            $startAt = fake()->dateTimeBetween('-6 months', '-1 day');

            return [
                'start_at' => $startAt->format('Y-m-d H:i:s'),
                'end_at' => (clone $startAt)->modify('+8 hours')->format('Y-m-d H:i:s'),
            ];
        });
    }
}
```

**Step 5: Update `tests/Feature/Offers/CourseTest.php`**

Replace the entire file (all `start_date` references → `start_at`/`end_at`):

```php
<?php

use App\Models\Course;
use App\Models\Offer;
use App\Models\User;

test('admin can add a course date to an offer', function () {
    $admin = User::factory()->create();
    $offer = Offer::factory()->create();

    $this->actingAs($admin)
        ->post(route('offers.courses.store', $offer), [
            'start_at' => '2026-06-01 09:00:00',
            'end_at' => '2026-06-01 17:00:00',
        ])
        ->assertRedirect(route('offers.edit', $offer));

    expect(Course::count())->toBe(1);
    expect(Course::first()->start_at->format('Y-m-d'))->toBe('2026-06-01');
    expect(Course::first()->offer_id)->toBe($offer->id);
});

test('admin can delete a course date', function () {
    $admin = User::factory()->create();
    $offer = Offer::factory()->create();
    $course = Course::factory()->for($offer)->create();

    $this->actingAs($admin)
        ->delete(route('offers.courses.destroy', [$offer, $course]))
        ->assertRedirect(route('offers.edit', $offer));

    expect(Course::find($course->id))->toBeNull();
});

test('instructor cannot add a course date', function () {
    $instructor = User::factory()->instructor()->create();
    $offer = Offer::factory()->create();

    $this->actingAs($instructor)
        ->post(route('offers.courses.store', $offer), [
            'start_at' => '2026-06-01 09:00:00',
            'end_at' => '2026-06-01 17:00:00',
        ])
        ->assertForbidden();
});

test('course start_at must be in the future', function () {
    $admin = User::factory()->create();
    $offer = Offer::factory()->create();

    $this->actingAs($admin)
        ->post(route('offers.courses.store', $offer), [
            'start_at' => '2020-01-01 09:00:00',
            'end_at' => '2020-01-01 17:00:00',
        ])
        ->assertSessionHasErrors('start_at');
});
```

**Step 6: Run CourseTest to verify it fails (StoreCourseRequest still uses start_date)**

```bash
php artisan test --compact --filter=CourseTest
```

Expected: FAIL (validation errors on `start_at` because `StoreCourseRequest` still expects `start_date`). This confirms the test is coupled to the real code — proceed to Task 2.

**Step 7: Commit the migration, model, factory, and updated test**

```bash
git add database/migrations/ app/Models/Course.php database/factories/CourseFactory.php tests/Feature/Offers/CourseTest.php
git commit -m "feat: replace courses.start_date with start_at/end_at datetime columns"
```

---

### Task 2: Update all downstream `start_date` references

**Files:**
- Modify: `app/Http/Requests/Offers/StoreCourseRequest.php`
- Modify: `app/Http/Requests/Enrollment/StoreEnrollmentRequest.php`
- Modify: `app/Actions/Enrollment/InitiateEnrollment.php`
- Modify: `app/Http/Controllers/Enrollment/EnrollmentController.php`
- Modify: `app/Http/Controllers/Offers/OfferController.php`
- Modify: `resources/js/pages/offers/edit.tsx`
- Modify: `tests/Feature/Enrollment/EnrollmentStoreTest.php`

**Step 1: Update `app/Http/Requests/Offers/StoreCourseRequest.php`**

Replace the `rules()` method:

```php
/**
 * @return array<string, array<mixed>>
 */
public function rules(): array
{
    return [
        'start_at' => ['required', 'date', 'after:now'],
        'end_at' => ['required', 'date', 'after:start_at'],
    ];
}
```

**Step 2: Update `app/Http/Requests/Enrollment/StoreEnrollmentRequest.php`**

In the `course_id` closure, change `$course->start_date` to `$course->start_at`:

```php
function (string $attribute, mixed $value, \Closure $fail) {
    $course = \App\Models\Course::find($value);
    if ($course && $course->start_at->isPast()) {
        $fail('Den valgte startdato er i fortiden.');
    }
},
```

**Step 3: Update `app/Actions/Enrollment/InitiateEnrollment.php`**

Change `$course->start_at->toDateString()` (it was `start_date`):

```php
$student = Student::create([
    'user_id' => $user->id,
    'phone' => $data['phone'] ?? null,
    'cpr' => $data['cpr'] ?? null,
    'status' => StudentStatus::Active,
    'start_date' => $course->start_at->toDateString(),
]);
```

**Step 4: Update `app/Http/Controllers/Enrollment/EnrollmentController.php`**

In `show()`, change `start_date` to `start_at` in the map and orderBy:

```php
public function show(Offer $offer): Response
{
    $courses = $offer->courses()->upcoming()->orderBy('start_at')->get();

    return Inertia::render('enroll', [
        'offer' => $offer,
        'stripePublishableKey' => config('services.stripe.publishable_key'),
        'availableDates' => $courses->map(fn ($c) => $c->start_at->format('Y-m-d'))->values(),
        'courses' => $courses->mapWithKeys(fn ($c) => [$c->start_at->format('Y-m-d') => $c->id]),
    ]);
}
```

**Step 5: Update `app/Http/Controllers/Offers/OfferController.php`**

In `edit()`, change `orderBy('start_date')` to `orderBy('start_at')`:

```php
'courses' => $offer->courses()->orderBy('start_at')->get(),
```

**Step 6: Update `resources/js/pages/offers/edit.tsx`**

Three changes in this file:

**a) Update the `Course` type** (near the top of the file, currently has `start_date: string`):

```tsx
type Course = {
    id: number;
    start_at: string;
    end_at: string;
    max_students: number | null;
};
```

**b) Update the course list display** (in the `<ul>` courses.map block):

Replace:
```tsx
<span>{new Date(course.start_date).toLocaleDateString('da-DK', { dateStyle: 'long' })}</span>
```

With:
```tsx
<span>
    {new Date(course.start_at).toLocaleString('da-DK', { dateStyle: 'long', timeStyle: 'short' })}
    {' – '}
    {new Date(course.end_at).toLocaleTimeString('da-DK', { timeStyle: 'short' })}
</span>
```

**c) Replace the single `date` input** with two `datetime-local` inputs in the `<Form {...storeCourse(offer)}>` block:

Replace:
```tsx
<input
    type="date"
    name="start_date"
    required
    min={new Date().toISOString().split('T')[0]}
    className="flex h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
/>
```

With:
```tsx
<input
    type="datetime-local"
    name="start_at"
    required
    min={new Date().toISOString().slice(0, 16)}
    className="flex h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
/>
<input
    type="datetime-local"
    name="end_at"
    required
    min={new Date().toISOString().slice(0, 16)}
    className="flex h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
/>
```

**Step 7: Update `tests/Feature/Enrollment/EnrollmentStoreTest.php`**

Replace the entire file:

```php
<?php

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Offer;
use App\Models\Student;

function validEnrollmentData(int $courseId): array
{
    return [
        'name' => 'Test Elev',
        'email' => 'elev@example.com',
        'phone' => '+45 12 34 56 78',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'payment_method' => 'cash',
        'course_id' => $courseId,
    ];
}

test('student can enroll with a valid course_id', function () {
    $offer = Offer::factory()->create();
    $startAt = now()->addWeeks(2);
    $course = Course::factory()->for($offer)->create([
        'start_at' => $startAt,
        'end_at' => $startAt->copy()->addHours(8),
    ]);

    $this->post(route('enrollment.store', $offer), validEnrollmentData($course->id))
        ->assertRedirect(route('dashboard'));

    $enrollment = Enrollment::first();
    expect($enrollment)->not->toBeNull();
    expect($enrollment->course_id)->toBe($course->id);

    $student = Student::first();
    expect($student->start_date->format('Y-m-d'))->toBe($course->start_at->format('Y-m-d'));
});

test('enrollment fails with a course that does not belong to the offer', function () {
    $offer = Offer::factory()->create();
    $otherOffer = Offer::factory()->create();
    $course = Course::factory()->for($otherOffer)->create();

    $this->post(route('enrollment.store', $offer), validEnrollmentData($course->id))
        ->assertSessionHasErrors('course_id');
});

test('enrollment fails with a past course date', function () {
    $offer = Offer::factory()->create();
    $course = Course::factory()->past()->for($offer)->create();

    $this->post(route('enrollment.store', $offer), validEnrollmentData($course->id))
        ->assertSessionHasErrors('course_id');
});

test('enrollment show page passes available dates for the offer', function () {
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
            ->has('availableDates', 1)
            ->has('courses')
        );
});
```

**Step 8: Run the affected tests**

```bash
php artisan test --compact --filter="CourseTest|EnrollmentStoreTest|EnrollmentCourseTest"
```

Expected: all green.

**Step 9: Run Pint**

```bash
vendor/bin/pint --dirty --format agent
```

**Step 10: Commit**

```bash
git add app/Http/Requests/Offers/StoreCourseRequest.php \
    app/Http/Requests/Enrollment/StoreEnrollmentRequest.php \
    app/Actions/Enrollment/InitiateEnrollment.php \
    app/Http/Controllers/Enrollment/EnrollmentController.php \
    app/Http/Controllers/Offers/OfferController.php \
    resources/js/pages/offers/edit.tsx \
    tests/Feature/Enrollment/EnrollmentStoreTest.php
git commit -m "feat: update all start_date references to start_at/end_at"
```

---

### Task 3: Create `Courses\CourseController`, `UpdateCourseRequest`, routes, and tests

**Files:**
- Create: `app/Http/Controllers/Courses/CourseController.php`
- Create: `app/Http/Requests/Courses/UpdateCourseRequest.php`
- Create: `tests/Feature/Courses/CourseAdminTest.php`
- Modify: `routes/web.php`

**Step 1: Write failing tests**

```bash
php artisan make:test Courses/CourseAdminTest --pest --no-interaction
```

Write `tests/Feature/Courses/CourseAdminTest.php`:

```php
<?php

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Offer;
use App\Models\User;

test('admin can view courses calendar', function () {
    $admin = User::factory()->create();
    Course::factory()->create();

    $this->actingAs($admin)
        ->get(route('courses.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('courses/index')
            ->has('events', 1)
            ->has('offers')
        );
});

test('admin can view course detail', function () {
    $admin = User::factory()->create();
    $course = Course::factory()->create();

    $this->actingAs($admin)
        ->get(route('courses.show', $course))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('courses/show')
            ->has('course')
        );
});

test('admin can update a course', function () {
    $admin = User::factory()->create();
    $course = Course::factory()->create();
    $startAt = now()->addWeeks(3);

    $this->actingAs($admin)
        ->patch(route('courses.update', $course), [
            'start_at' => $startAt->format('Y-m-d H:i:s'),
            'end_at' => $startAt->copy()->addHours(8)->format('Y-m-d H:i:s'),
            'max_students' => 15,
        ])
        ->assertRedirect(route('courses.show', $course));

    expect($course->fresh()->max_students)->toBe(15);
});

test('instructor cannot update a course', function () {
    $instructor = User::factory()->instructor()->create();
    $course = Course::factory()->create();
    $startAt = now()->addWeeks(3);

    $this->actingAs($instructor)
        ->patch(route('courses.update', $course), [
            'start_at' => $startAt->format('Y-m-d H:i:s'),
            'end_at' => $startAt->copy()->addHours(8)->format('Y-m-d H:i:s'),
        ])
        ->assertForbidden();
});

test('course detail includes enrolled students', function () {
    $admin = User::factory()->create();
    $offer = Offer::factory()->create();
    $course = Course::factory()->for($offer)->create();
    Enrollment::factory()->create(['course_id' => $course->id, 'offer_id' => $offer->id]);

    $this->actingAs($admin)
        ->get(route('courses.show', $course))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('course.enrollments', 1)
        );
});
```

**Step 2: Run to verify they fail**

```bash
php artisan test --compact --filter=CourseAdminTest
```

Expected: FAIL (routes don't exist yet).

**Step 3: Add routes to `routes/web.php`**

Inside the `auth + verified` middleware group, add after the existing `offers.courses` resource:

```php
Route::get('courses', [\App\Http\Controllers\Courses\CourseController::class, 'index'])->name('courses.index');
Route::get('courses/{course}', [\App\Http\Controllers\Courses\CourseController::class, 'show'])->name('courses.show');
Route::patch('courses/{course}', [\App\Http\Controllers\Courses\CourseController::class, 'update'])->name('courses.update');
```

**Step 4: Create the form request**

```bash
php artisan make:request Courses/UpdateCourseRequest --no-interaction
```

Write `app/Http/Requests/Courses/UpdateCourseRequest.php`:

```php
<?php

namespace App\Http\Requests\Courses;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('course')->offer);
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'start_at' => ['required', 'date', 'after:now'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'max_students' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
```

**Step 5: Create the controller**

```bash
php artisan make:controller Courses/CourseController --no-interaction
```

Write `app/Http/Controllers/Courses/CourseController.php`:

```php
<?php

namespace App\Http\Controllers\Courses;

use App\Http\Controllers\Controller;
use App\Http\Requests\Courses\UpdateCourseRequest;
use App\Models\Course;
use App\Models\Offer;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    public function index(): Response
    {
        $palette = ['#2563eb', '#16a34a', '#dc2626', '#9333ea', '#d97706', '#0891b2'];

        $courses = Course::query()
            ->with('offer')
            ->orderBy('start_at')
            ->get()
            ->map(fn (Course $course) => [
                'id' => (string) $course->id,
                'title' => $course->offer->name,
                'start' => $course->start_at->format('Y-m-d H:i'),
                'end' => $course->end_at->format('Y-m-d H:i'),
                'calendarId' => 'offer-' . $course->offer_id,
                'offer_id' => $course->offer_id,
            ]);

        $offers = Offer::query()
            ->whereHas('courses')
            ->get(['id', 'name'])
            ->values()
            ->map(fn (Offer $offer, int $index) => [
                'id' => $offer->id,
                'name' => $offer->name,
                'color' => $palette[$offer->id % count($palette)],
            ]);

        return Inertia::render('courses/index', [
            'events' => $courses,
            'offers' => $offers,
        ]);
    }

    public function show(Course $course): Response
    {
        $course->load(['offer', 'enrollments.student.user']);

        return Inertia::render('courses/show', [
            'course' => [
                'id' => $course->id,
                'start_at' => $course->start_at->toIso8601String(),
                'end_at' => $course->end_at->toIso8601String(),
                'max_students' => $course->max_students,
                'offer' => [
                    'id' => $course->offer->id,
                    'name' => $course->offer->name,
                ],
                'enrollments' => $course->enrollments->map(fn ($enrollment) => [
                    'id' => $enrollment->id,
                    'status' => $enrollment->status->value,
                    'payment_method' => $enrollment->payment_method->value,
                    'student' => [
                        'id' => $enrollment->student->id,
                        'name' => $enrollment->student->user->name,
                        'email' => $enrollment->student->user->email,
                    ],
                ]),
            ],
        ]);
    }

    public function update(UpdateCourseRequest $request, Course $course): RedirectResponse
    {
        $course->update($request->validated());

        return redirect()->route('courses.show', $course)
            ->with('success', 'Kursus opdateret.');
    }
}
```

**Step 6: Regenerate Wayfinder**

```bash
php artisan wayfinder:generate --no-interaction
```

**Step 7: Run the tests**

```bash
php artisan test --compact --filter=CourseAdminTest
```

Expected: all 5 pass.

**Step 8: Run Pint**

```bash
vendor/bin/pint --dirty --format agent
```

**Step 9: Commit**

```bash
git add app/Http/Controllers/Courses/CourseController.php \
    app/Http/Requests/Courses/UpdateCourseRequest.php \
    routes/web.php \
    tests/Feature/Courses/CourseAdminTest.php
git commit -m "feat: add Courses\CourseController (index, show, update) with routes and tests"
```

---

### Task 4: Install @schedule-x and create `courses/index.tsx`

**Files:**
- Create: `resources/js/pages/courses/index.tsx`

**Step 1: Check docs first**

Use the `search-docs` tool with queries `['schedule-x react', 'schedule-x calendar views', 'schedule-x event click']` to confirm the exact API for `@schedule-x/react` v2 before writing code.

**Step 2: Install @schedule-x packages**

```bash
npm install @schedule-x/react @schedule-x/calendar @schedule-x/theme-default
```

Expected: packages added to `node_modules` and `package.json`.

**Step 3: Create `resources/js/pages/courses/index.tsx`**

```tsx
import { router } from '@inertiajs/react';
import { useCalendarApp, ScheduleXCalendar } from '@schedule-x/react';
import { createViewMonthGrid, createViewWeek } from '@schedule-x/calendar';
import '@schedule-x/theme-default/dist/index.css';
import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { show } from '@/actions/App/Http/Controllers/Courses/CourseController';

type CalendarEvent = {
    id: string;
    title: string;
    start: string;
    end: string;
    calendarId: string;
    offer_id: number;
};

type OfferMeta = {
    id: number;
    name: string;
    color: string;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Kurser', href: '#' },
];

export default function CoursesIndex({
    events,
    offers,
}: {
    events: CalendarEvent[];
    offers: OfferMeta[];
}) {
    const calendarsConfig = Object.fromEntries(
        offers.map((offer) => [
            `offer-${offer.id}`,
            {
                colorName: `offer-${offer.id}`,
                lightColors: {
                    main: offer.color,
                    container: offer.color + '33',
                    onContainer: offer.color,
                },
            },
        ]),
    );

    const calendar = useCalendarApp({
        defaultView: createViewMonthGrid().name,
        views: [createViewMonthGrid(), createViewWeek()],
        events,
        calendars: calendarsConfig,
        callbacks: {
            onEventClick(event: CalendarEvent) {
                router.visit(show({ course: event.id }).url);
            },
        },
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Kurser" />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <Heading title="Kurser" />
                <ScheduleXCalendar calendarApp={calendar} />
            </div>
        </AppLayout>
    );
}
```

> **Note:** If the @schedule-x API differs from above (e.g. `createCalendar` wrapper required, or `onEventClick` is named differently), check the docs retrieved in Step 1 and adjust. The pattern is always: create config → pass to `useCalendarApp` → render `<ScheduleXCalendar calendarApp={...} />`.

**Step 4: Build to check for TypeScript errors**

```bash
npm run build 2>&1 | tail -30
```

Expected: no TypeScript errors. Fix any import path or type errors before continuing.

**Step 5: Commit**

```bash
git add resources/js/pages/courses/index.tsx package.json package-lock.json
git commit -m "feat: add courses calendar index page with @schedule-x month/week views"
```

---

### Task 5: Create `courses/show.tsx` and add sidebar nav item

**Files:**
- Create: `resources/js/pages/courses/show.tsx`
- Modify: `resources/js/components/app-sidebar.tsx`

**Step 1: Regenerate Wayfinder (if not done recently)**

```bash
php artisan wayfinder:generate --no-interaction
```

Verify `resources/js/actions/App/Http/Controllers/Courses/CourseController.ts` exists with `update` export.

**Step 2: Create `resources/js/pages/courses/show.tsx`**

```tsx
import { Head, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { index } from '@/routes/courses';
import { update } from '@/actions/App/Http/Controllers/Courses/CourseController';
import { destroy } from '@/actions/App/Http/Controllers/Offers/CourseController';
import { Form } from '@inertiajs/react';

type Enrollment = {
    id: number;
    status: string;
    payment_method: string;
    student: { id: number; name: string; email: string };
};

type CourseDetail = {
    id: number;
    start_at: string;
    end_at: string;
    max_students: number | null;
    offer: { id: number; name: string };
    enrollments: Enrollment[];
};

export default function CourseShow({ course }: { course: CourseDetail }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Kurser', href: index().url },
        { title: course.offer.name, href: '#' },
    ];

    const form = useForm({
        start_at: new Date(course.start_at).toISOString().slice(0, 16),
        end_at: new Date(course.end_at).toISOString().slice(0, 16),
        max_students: course.max_students ? String(course.max_students) : '',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        form.submit(update(course));
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${course.offer.name} – kursus`} />

            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center gap-3">
                    <Link href={index().url} className="text-muted-foreground hover:text-foreground">
                        <ArrowLeft className="size-4" />
                    </Link>
                    <Heading title={course.offer.name} />
                </div>

                {/* Edit form */}
                <div className="max-w-lg">
                    <h2 className="text-base font-semibold mb-4">Kursusdato</h2>
                    <form onSubmit={handleSubmit} className="space-y-4">
                        <div className="grid sm:grid-cols-2 gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="start_at">Start</Label>
                                <Input
                                    id="start_at"
                                    type="datetime-local"
                                    value={form.data.start_at}
                                    onChange={(e) => form.setData('start_at', e.target.value)}
                                    required
                                />
                                <InputError message={form.errors.start_at} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="end_at">Slut</Label>
                                <Input
                                    id="end_at"
                                    type="datetime-local"
                                    value={form.data.end_at}
                                    onChange={(e) => form.setData('end_at', e.target.value)}
                                    required
                                />
                                <InputError message={form.errors.end_at} />
                            </div>
                        </div>
                        <div className="grid gap-2 max-w-[160px]">
                            <Label htmlFor="max_students">Maks. elever</Label>
                            <Input
                                id="max_students"
                                type="number"
                                min="1"
                                value={form.data.max_students}
                                onChange={(e) => form.setData('max_students', e.target.value)}
                                placeholder="Ingen grænse"
                            />
                            <InputError message={form.errors.max_students} />
                        </div>
                        <div className="flex items-center gap-3">
                            <Button type="submit" disabled={form.processing}>
                                Gem ændringer
                            </Button>
                            <Form
                                {...destroy({ offer: course.offer, course })}
                                method="delete"
                                onBefore={() => confirm('Er du sikker på, at du vil slette dette kursus?')}
                            >
                                {({ processing }) => (
                                    <Button type="submit" variant="destructive" disabled={processing}>
                                        Slet kursus
                                    </Button>
                                )}
                            </Form>
                        </div>
                    </form>
                </div>

                {/* Enrollments table */}
                <div className="max-w-2xl">
                    <h2 className="text-base font-semibold mb-4">
                        Tilmeldte ({course.enrollments.length}
                        {course.max_students ? ` / ${course.max_students}` : ''})
                    </h2>

                    {course.enrollments.length === 0 ? (
                        <p className="text-sm text-muted-foreground">Ingen tilmeldte endnu.</p>
                    ) : (
                        <div className="rounded-md border">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50">
                                        <th className="px-4 py-2 text-left font-medium">Navn</th>
                                        <th className="px-4 py-2 text-left font-medium">E-mail</th>
                                        <th className="px-4 py-2 text-left font-medium">Betaling</th>
                                        <th className="px-4 py-2 text-left font-medium">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {course.enrollments.map((enrollment) => (
                                        <tr key={enrollment.id} className="border-b last:border-0">
                                            <td className="px-4 py-2">{enrollment.student.name}</td>
                                            <td className="px-4 py-2 text-muted-foreground">{enrollment.student.email}</td>
                                            <td className="px-4 py-2">{enrollment.payment_method}</td>
                                            <td className="px-4 py-2">{enrollment.status}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
```

**Step 3: Add "Kurser" to sidebar nav**

In `resources/js/components/app-sidebar.tsx`:

Add this import after the existing route imports:

```tsx
import { index as coursesIndex } from '@/routes/courses';
```

Add this item to `mainNavItems` after the `Tilbud` entry:

```tsx
{
    title: 'Kurser',
    href: coursesIndex(),
    icon: CalendarDays,
},
```

Note: `CalendarDays` is already imported in the sidebar. If not, add it to the `lucide-react` import.

**Step 4: Build to check for TypeScript errors**

```bash
npm run build 2>&1 | tail -30
```

Expected: no TypeScript errors. If `@/routes/courses` doesn't exist yet, regenerate Wayfinder and rebuild.

**Step 5: Commit**

```bash
git add resources/js/pages/courses/show.tsx resources/js/components/app-sidebar.tsx
git commit -m "feat: add course detail page and sidebar nav item"
```

---

### Task 6: Pint + full test suite

**Step 1: Run Pint on all dirty PHP files**

```bash
vendor/bin/pint --dirty --format agent
```

**Step 2: Run the full test suite**

```bash
php artisan test --compact
```

Expected: all green. If any test fails, investigate and fix before declaring done.

**Step 3: Commit Pint fixes if any**

```bash
git add -p
git commit -m "style: apply pint formatting"
```
