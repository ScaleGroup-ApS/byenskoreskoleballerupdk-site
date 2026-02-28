# Rename EnrollmentRequest → Enrollment

**Goal:** Rename `EnrollmentRequest` to `Enrollment` throughout the codebase. The "Request" suffix is misleading — the record tracks the full lifecycle of an enrollment (pending, approved, rejected), not just the initial application. The verb event trail (`EnrollmentApproved`, `EnrollmentRejected`) provides the audit log, making `approved_by_id` on the model redundant.

---

## Changes

### Migration (one migration)
- `RENAME TABLE enrollment_requests TO enrollments`
- `DROP COLUMN approved_by_id`

### Files renamed
| From | To |
|------|----|
| `app/Models/EnrollmentRequest.php` | `app/Models/Enrollment.php` |
| `database/factories/EnrollmentRequestFactory.php` | `database/factories/EnrollmentFactory.php` |
| `app/States/EnrollmentRequestState.php` | `app/States/EnrollmentState.php` |

### References updated (~15 files)
- Class names and type hints: `EnrollmentRequest` → `Enrollment`
- Variable names: `$enrollmentRequest` → `$enrollment`, `$enrollmentRequests` → `$enrollments`
- Relationship on `Course`: `enrollmentRequests()` → `enrollments()`
- Event parameter: `enrollment_request_id` → `enrollment_id`
- Route binding parameter: `{enrollment}` (already correct in routes, used in `EnrollmentApprovalController`)

### Not renamed
- `StoreEnrollmentRequest`, `RejectEnrollmentRequest` — Laravel Form Requests; `Request` suffix is convention
- `EnrollmentRequested`, `EnrollmentApproved`, `EnrollmentRejected` events — describe actions, not the model

---

## Files touched
- `app/Models/Enrollment.php` (renamed)
- `app/Models/Course.php`
- `database/factories/EnrollmentFactory.php` (renamed)
- `app/States/EnrollmentState.php` (renamed)
- `database/migrations/` (new rename+drop migration)
- `app/Actions/Enrollment/ApproveEnrollment.php`
- `app/Actions/Enrollment/CompleteStripeEnrollment.php`
- `app/Actions/Enrollment/CreateStripeCheckoutSession.php`
- `app/Actions/Enrollment/InitiateEnrollment.php`
- `app/Actions/Enrollment/RejectEnrollment.php`
- `app/Events/EnrollmentApproved.php`
- `app/Events/EnrollmentRejected.php`
- `app/Events/EnrollmentRequested.php`
- `app/Events/StripePaymentCompleted.php`
- `app/Http/Controllers/Enrollment/EnrollmentApprovalController.php`
- `app/Http/Controllers/Enrollment/EnrollmentController.php`
- `app/Http/Controllers/DashboardController.php`
- `app/Http/Requests/Enrollment/StoreEnrollmentRequest.php`
- `app/Notifications/EnrollmentApprovedNotification.php`
- `app/Notifications/EnrollmentRejectedNotification.php`
- `resources/js/pages/enrollments/index.tsx`
- `tests/Feature/Enrollment/EnrollmentCourseTest.php`
