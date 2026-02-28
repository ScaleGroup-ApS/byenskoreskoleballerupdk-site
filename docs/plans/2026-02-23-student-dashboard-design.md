# Student Dashboard Design

**Date:** 2026-02-23

## Overview

Students currently land on `/dashboard` which is designed for staff (admins and instructors). This plan introduces a dedicated student dashboard at `/student` and redirects students away from `/dashboard`.

## Routes & Controllers

- `GET /student` → `App\Http\Controllers\Student\StudentDashboardController` (invokable), named `student.dashboard`
- `DashboardController::__invoke` redirects students early: `if ($user->isStudent()) return redirect()->route('student.dashboard')`
- Non-students (admin, instructor) visiting `/student` receive a 403

## Data

The `StudentDashboardController` loads:

- **Upcoming booking** — next booking from `bookings` where `student_id = student->id` and `starts_at >= now()`, ordered by `starts_at`, limit 1. Exposes `type`, `starts_at`, `ends_at`.
- **Progression** — output of existing `CheckExamReadiness` and `CalculateBalance` actions, passed as `readiness` and `balance`.

If the authenticated user has no `student` record, abort with 404.

## Frontend

`resources/js/pages/student/index.tsx` using `AppLayout`:

1. **Næste lektion** — upcoming booking card, or empty state "Ingen kommende lektioner"
2. **Progression** — condensed readiness checklist + outstanding balance

## Testing

- Student visiting `/dashboard` → redirected to `student.dashboard`
- Admin/instructor visiting `/student` → 403
- Student visiting `/student` → Inertia page `student/index` with `booking` and `progression` props
