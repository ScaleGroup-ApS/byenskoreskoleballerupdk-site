# Driving School Management Platform — Design Document (v2)

**Date:** 2026-02-21 (updated)
**Stack:** Laravel 12 + Inertia v2 (React 19) + Fortify + MariaDB + Tailwind 4 + Verbs + Spatie Media Library

## Architecture Decisions

### Directory Structure: Flat Domain Folders
All business logic in Actions (no service classes). Controllers are thin orchestrators.

```
app/
├── Actions/{Domain}/       Single-responsibility action classes with handle()
├── Data/                   Simple DTOs for complex flows
├── Enums/                  All enums
├── Events/                 Verbs events (StudentEnrolled, BookingCreated, etc.)
├── States/                 Verbs states (StudentProgressionState, StudentBalanceState)
├── Models/                 Eloquent models
├── Policies/               Authorization policies
├── Http/Controllers/       Thin controllers calling Actions
├── Http/Requests/          FormRequest validation
├── Jobs/                   Queued jobs
└── Notifications/          Email + database notifications
```

### Role System: Enum Column on Users
Three roles: Admin, Instructor, Student. Simple `role` enum column on users table.

### User + Student Profile
- **User** model handles authentication for all roles.
- **Student** model holds domain data linked via `user_id` FK.
- Creating a student creates both a User (role=student) and a Student profile.

### Event Sourcing: Hybrid with Verbs
- **Verbs events** for: Students, Bookings, Payments, Progression (audit trail matters)
- **Simple Eloquent CRUD** for: Vehicles, Blog, Offers, Teams (low complexity)
- Verbs replaces AuditLog — event history IS the audit trail

### Media: Spatie Media Library
- Replaces custom Document model
- `HasMedia` trait on Student (collections: "documents", "photos") and BlogPost (collection: "featured")
- Handles conversions, responsive images, storage abstraction

### Chat: SSE Streaming
- Conversation model (direct + group types)
- Laravel 12 native `response()->eventStream()` + `@laravel/stream-react`
- Team group chat + 1-on-1 DMs

### Notifications: Database Channel
- Laravel database notification channel for in-app notifications
- Shared via Inertia as `auth.notifications`
- Bell icon in UI with unread count

## Dependencies (New)

| Package | Purpose |
|---------|---------|
| `thunk/verbs` | Event sourcing for key domain flows |
| `spatie/laravel-medialibrary` | Media/file management |
| `@laravel/stream-react` | SSE consumption in React |

## Data Model

### Users
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| name | string | |
| email | string | unique |
| password | string | hashed |
| role | enum | admin/instructor/student |
| email_verified_at | timestamp | nullable |
| two_factor_* | - | Fortify 2FA columns |

### Students
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| user_id | bigint | FK → users, unique |
| phone | string | nullable |
| cpr | text | encrypted via custom cast |
| status | enum | active/inactive/graduated/dropped_out |
| start_date | date | nullable |
| deleted_at | timestamp | soft deletes |

Uses Spatie Media Library (`HasMedia`) with collections: `documents`, `photos`.

### Teams
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| name | string | |
| description | text | nullable |
| timestamps | | |

Pivot: `student_team` (student_id, team_id)

Flexible grouping — no fixed dates or instructor. Just a way to organize/filter students.

### Vehicles
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| name | string | e.g. "Toyota Yaris #1" |
| plate_number | string | unique |
| active | boolean | default true |

### Offers (Schema.org Offer, was "Package")
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| name | string | schema:name |
| description | text | schema:description, nullable |
| price | decimal(10,2) | schema:price |
| type | enum | primary / addon |
| theory_lessons | integer | |
| driving_lessons | integer | |
| track_required | boolean | |
| slippery_required | boolean | |

Pivot: `offer_student` (offer_id, student_id, assigned_at)

Students get one primary offer + multiple addon offers.

### Bookings
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| student_id | bigint | FK → students |
| instructor_id | bigint | FK → users (instructor) |
| vehicle_id | bigint | FK → vehicles, nullable |
| start_time | datetime | |
| end_time | datetime | |
| type | enum | driving_lesson/theory_lesson/track_driving/slippery_driving/exam |
| status | enum | scheduled/completed/cancelled/no_show |

### Payments
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| student_id | bigint | FK → students |
| amount | decimal(10,2) | |
| method | enum | cash/card/mobile_pay/invoice |
| paid_at | timestamp | |

### Blog Posts
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| title | string | |
| slug | string | unique |
| content | longtext | |
| published_at | timestamp | nullable |
| seo_description | string | nullable |

Uses Spatie Media Library for featured images (collection: `featured`).

### Conversations
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| type | enum | direct / group |
| team_id | bigint | FK → teams, nullable (for group chats) |
| name | string | nullable (for group chats) |
| timestamps | | |

Pivot: `conversation_user` (conversation_id, user_id, last_read_at)

### Messages
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | PK |
| conversation_id | bigint | FK → conversations |
| user_id | bigint | FK → users |
| body | text | |
| timestamps | | |

### Notifications (Laravel built-in)
Uses `php artisan make:notifications-table` — standard Laravel notifications table.

## Verbs Events & States

### Events
| Event | Triggers When | Affects State |
|-------|---------------|---------------|
| `StudentEnrolled` | Student created | StudentProgressionState |
| `StudentStatusChanged` | Status updated | — |
| `OfferAssigned` | Offer assigned to student | StudentBalanceState |
| `BookingCreated` | Booking created | StudentProgressionState |
| `BookingCompleted` | Booking marked complete | StudentProgressionState |
| `BookingCancelled` | Booking cancelled | StudentProgressionState |
| `BookingNoShow` | No-show flagged | StudentProgressionState |
| `PaymentRecorded` | Payment created | StudentBalanceState |

### States
| State | Derived From | Provides |
|-------|-------------|----------|
| `StudentProgressionState` | Booking events | Completed lesson counts by type, exam readiness boolean, missing requirements |
| `StudentBalanceState` | OfferAssigned + PaymentRecorded | Total owed, total paid, outstanding balance |

## Enums

- **UserRole:** Admin, Instructor, Student
- **StudentStatus:** Active, Inactive, Graduated, DroppedOut
- **OfferType:** Primary, Addon
- **BookingType:** DrivingLesson, TheoryLesson, TrackDriving, SlipperyDriving, Exam
- **BookingStatus:** Scheduled, Completed, Cancelled, NoShow
- **PaymentMethod:** Cash, Card, MobilePay, Invoice
- **ConversationType:** Direct, Group

## Key Business Rules

1. **Booking conflicts:** No instructor or vehicle double-booked. Students cannot have overlapping bookings.
2. **CPR encryption:** Custom Eloquent cast using Laravel's encrypt/decrypt.
3. **Exam readiness:** Derived from Verbs `StudentProgressionState`. Student must have completed all required lesson types from assigned offers.
4. **Balance calculation:** Derived from Verbs `StudentBalanceState`. Sum of offer prices minus sum of payments.
5. **Role access:** Admin sees everything. Instructor sees own bookings + assigned students. Student sees own data only.
6. **No-show auto-flag:** Scheduled job fires `BookingNoShow` Verbs event for past unresolved bookings.
7. **Chat access:** Users can only access conversations they're participants of. Team group chats auto-include team members.

## Implementation Phases

### Phase 0 — Foundation
- 0.1: Auth & Roles (role enum, middleware, policies, admin seed)
- 0.2: Enums, CPR encryption cast
- 0.3: Verbs setup (install, configure)
- 0.4: White-label theming system
- 0.5: Disable public registration
- 0.6: Spatie Media Library setup
- 0.7: In-app notifications setup (database channel, notification bell)

### Phase 1 — Students & Teams
- 1.1: Student model, migration, factory, CRUD actions, policy, Inertia pages
- 1.2: Media uploads via Spatie (replace Document model)
- 1.3: Teams model (CRUD, M:M student assignment)
- 1.4: Verbs events: StudentEnrolled, StudentStatusChanged

### Phase 2 — Vehicles & Offers
- 2.1: Vehicle model (simple CRUD)
- 2.2: Offer model (Schema.org, type enum, CRUD)
- 2.3: Offer assignment + Verbs event: OfferAssigned

### Phase 3 — Bookings
- 3.1: Booking model, conflict detection, CRUD, calendar UI
- 3.2: Verbs events: BookingCreated, BookingCompleted, BookingCancelled
- 3.3: Drag & drop update with conflict re-check

### Phase 4 — Payments
- 4.1: Payment CRUD
- 4.2: Verbs event: PaymentRecorded + StudentBalanceState

### Phase 5 — Progression
- 5.1: StudentProgressionState (derived from booking events)
- 5.2: Exam readiness indicator + progression page

### Phase 6 — Dashboard
- 6.1: KPI actions + dashboard page

### Phase 7 — Blog
- 7.1: Blog CRUD with Spatie Media for featured images

### Phase 8 — Reminders & Notifications
- 8.1: Booking reminder job + BookingNoShow event
- 8.2: In-app notification types (BookingReminder, PaymentReceived, etc.)

### Phase 9 — Chat
- 9.1: Conversation + Message models
- 9.2: Chat controller with SSE streaming
- 9.3: Chat UI (conversation list, message thread, team group chat)

Each phase follows: Implement → Test → Run tests → Refactor → Commit.

## White-Label / Theming Strategy

This application is white-labelled — each deployment represents a different driving school.

### What's Configurable Per Deployment

1. **Brand colors** — Primary, accent, sidebar via CSS custom properties (oklch tokens)
2. **Logo** — Loaded from Spatie Media or config, fallback to default SVG
3. **App name** — `config('app.name')`
4. **Font** — Optional `--font-sans` override

### Implementation

- `config/branding.php` with env-driven color overrides
- `HandleInertiaRequests` shares branding config
- `<ThemeProvider>` component injects CSS variable overrides
- No rebuild required — colors are runtime CSS variables
