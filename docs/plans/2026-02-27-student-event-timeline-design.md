# Student Event Timeline — Design

**Date:** 2026-02-27
**Scope:** Add a per-student Verbs event timeline section to `resources/js/pages/students/show.tsx`.

## Problem

Admins have no visibility into a student's event history (Verbs events). Events like bookings, enrollments, and payments are stored in `verb_events` but not surfaced anywhere on the student detail page.

## Design

### Data Source

All 12 Verbs event types carry `student_id` in their JSON `data` column. Query:

```php
VerbEvent::where('data->student_id', $student->id)->latest()->get()
```

This catches all event types regardless of which state they use.

### Backend Changes

**`StudentController::show()`** — add `eventTimeline` prop (only when `canEdit`, i.e. admin):

```php
'eventTimeline' => $canEdit
    ? VerbEvent::query()
        ->where('data->student_id', $student->id)
        ->latest()
        ->get()
        ->map(fn ($event) => [
            'id' => $event->id,
            'summary' => ...,   // Danish human-readable string
            'category' => ...,  // booking | enrollment | student | payment
            'created_at' => $event->created_at->toISOString(),
        ])
        ->all()
    : [],
```

**Event summary mapping (inline in controller):**

| Event type | Summary | Category |
|---|---|---|
| BookingCreated | "Booking oprettet" | booking |
| BookingCompleted | "Booking gennemført" | booking |
| BookingCancelled | "Booking annulleret" | booking |
| BookingNoShow | "Elev mødte ikke op" | booking |
| EnrollmentRequested | "Tilmelding anmodet" | enrollment |
| EnrollmentApproved | "Tilmelding godkendt" | enrollment |
| EnrollmentRejected | "Tilmelding afvist" | enrollment |
| StudentEnrolled | "Elev tilmeldt" | student |
| StudentStatusChanged | "Status ændret → {new_status}" | student |
| OfferAssigned | "Tilbud tildelt: {offer_name}" | payment |
| PaymentRecorded | "Betaling registreret: {amount} kr." | payment |
| StripePaymentCompleted | "Stripe-betaling gennemført" | payment |

### Frontend Changes

**`resources/js/pages/students/show.tsx`** — new "Hændelseslog" section (admin only):

- Vertical timeline: left border line + colored circle dots
- Category colors: booking=blue, enrollment=purple, student=green, payment=amber
- Each entry: summary text + formatted Danish timestamp
- Empty state if no events

### Visibility

Timeline is only shown when `canEdit` is true (admin). Students and instructors do not see it.

### Auth

No additional middleware needed — existing `StudentController::show()` already uses `$this->authorize('view', $student)` and `canEdit` is already computed as `$request->user()->isAdmin()`.
