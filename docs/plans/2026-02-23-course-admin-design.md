# Course Admin Pages Design

**Goal:** Add dedicated course admin pages — a Google Calendar-style grid showing all courses across all offers, and a per-course detail page. Course dates gain a time component (`start_at` + `end_at`).

---

## Data Model

Replace `start_date DATE` with `start_at DATETIME` + `end_at DATETIME` on the `courses` table.

One migration:
- `RENAME COLUMN start_date → start_at`, change type to `DATETIME`
- `ADD COLUMN end_at DATETIME AFTER start_at`

Model changes:
- Casts: `start_at` → `datetime`, `end_at` → `datetime` (drop `start_date`)
- `scopeUpcoming`: filter `start_at >= now()`
- `$fillable`: replace `start_date` with `start_at`, `end_at`

Downstream updates (all `start_date` references become `start_at`):
- `StoreCourseRequest` — validate `start_at` (after:now) and `end_at` (after:start_at)
- `InitiateEnrollment` — `$course->start_at->toDateString()`
- `EnrollmentController@show` — map `start_at` for `availableDates` and `courses`
- `CourseFactory` — generate datetime pairs
- `offers/edit.tsx` — two `datetime-local` inputs instead of one `date` input
- `enroll.tsx` — `availableDates` array uses `start_at` date portion (no change to format, just source field)

---

## Routes

New routes added alongside existing nested ones:

```php
// New top-level course pages
Route::get('courses', [CourseController::class, 'index'])->name('courses.index');
Route::get('courses/{course}', [CourseController::class, 'show'])->name('courses.show');
Route::patch('courses/{course}', [CourseController::class, 'update'])->name('courses.update');

// Existing (unchanged)
Route::resource('offers.courses', \App\Http\Controllers\Offers\CourseController::class)
    ->only(['store', 'destroy']);
```

New controller: `app/Http/Controllers/Courses/CourseController.php` (`index`, `show`, `update`).
Existing: `app/Http/Controllers/Offers/CourseController.php` (`store`, `destroy`) — untouched.

---

## Pages

### `/courses` — Calendar index

- `@schedule-x/react` calendar with month + week view toggle
- Each course = one event: title = offer name, color-coded by `offer_id` (same offer → same color across all dates)
- Month view: event chips per day
- Week view: time-positioned blocks, height proportional to duration
- Clicking an event navigates to `/courses/{id}`
- "Tilføj kursus" button links to the relevant offer's edit page (course creation stays in offers/edit)
- Sidebar nav: new "Kurser" item

### `/courses/{id}` — Course detail

- Header: offer name, `start_at` → `end_at`, enrolled / max capacity
- Edit card: `start_at`, `end_at`, `max_students` inputs, save button (PATCH)
- Enrollments table: student name, email, payment method, status
- Delete button with confirmation (reuses existing `offers.courses.destroy` route)

---

## Frontend Stack

- `@schedule-x/react` + `@schedule-x/calendar` + `@schedule-x/theme-default`
- No additional date libraries needed beyond already-installed `date-fns`

---

## Not in scope

- Drag-to-reschedule on calendar
- Creating courses from the calendar (stays in offers/edit)
- Student-facing course calendar
