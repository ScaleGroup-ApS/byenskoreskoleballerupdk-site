# Design: Course Model & Enrollment Date Picker

**Date:** 2026-02-23
**Status:** Approved

## Problem

The enrollment form has a free-text `start_date` field. There is no concept of a scheduled course run, so students can pick any arbitrary date and the school has no way to control which dates are valid.

## Approved Solution: Course Model (Option A)

Introduce a `Course` model that represents a specific dated run of an `Offer`. Students enroll into a Course, not just an Offer. The enrollment day picker shows only dates where future Courses exist for the selected Offer.

---

## 1. Data Model

### New `courses` table

| Column        | Type              | Notes                          |
|---------------|-------------------|--------------------------------|
| id            | bigint unsigned   | PK                             |
| offer_id      | bigint unsigned   | FK → offers, cascade delete    |
| start_date    | date              |                                |
| max_students  | int, nullable     | Optional capacity cap          |
| created_at    | timestamp         |                                |
| updated_at    | timestamp         |                                |

### Modified `enrollment_requests` table

Add a nullable `course_id` FK (bigint unsigned → courses, set null on delete).

---

## 2. Backend

### Model: `App\Models\Course`

- `belongsTo(Offer::class)`
- `hasMany(EnrollmentRequest::class)`
- Scope: `upcoming()` — `start_date >= today`

### Controller: `Offers\CourseController`

Nested resource under offers: `offers/{offer}/courses`.

- `index` — list all courses for an offer (used in admin offer edit page)
- `store` — create a new course date
- `destroy` — remove a course date

### `EnrollmentController@show`

Pass `availableDates` (array of ISO date strings from upcoming courses for this offer) and `courses` (id → date map) as Inertia props alongside the existing `offer`.

### `StoreEnrollmentRequest`

- Replace `start_date` (nullable date) with `course_id` (required, exists in `courses` table, `offer_id` must match route offer, `start_date` must be in the future).

### `InitiateEnrollment` action

- Accept `course_id` in validated data.
- Set `student.start_date` from the selected Course's `start_date`.
- Link `enrollment_request.course_id` to the selected Course.

---

## 3. Frontend — Enrollment Form

### shadcn Calendar + Popover

Install the shadcn `calendar` and `popover` components (backed by react-day-picker). These are not yet in the project.

### Behaviour

- Inertia prop `availableDates: string[]` passed from the controller.
- Replace `<Input type="date">` with a `<Popover>` trigger button showing the selected date, containing a `<Calendar>` where only dates in `availableDates` are enabled (all others `disabled`).
- On date select, look up the matching `course_id` from the `courses` map and write it to a hidden `<input name="course_id">`.
- Danish locale (da) for the calendar.

---

## 4. Admin — Course Management

Add a "Kursusdatoer" (Course dates) section to the Offer edit page (`offers/{offer}/edit`). It lists existing courses with a delete button and a small "Add date" form (date picker + optional max students).

No separate courses index route is needed — management is inline on the offer.

---

## 5. Testing

- Feature test: `CourseController` store/destroy.
- Feature test: `EnrollmentController@store` with a valid `course_id` succeeds; with an invalid or past-dated `course_id` fails validation.
- Update existing enrollment tests to use `course_id`.

---

## Out of Scope

- Capacity enforcement (max_students validation) — add later.
- Student-facing course roster / listing.
- Course editing (date change) — delete and recreate instead.
