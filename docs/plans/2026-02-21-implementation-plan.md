# Driving School Platform — Implementation Plan (v2)

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Build a production-ready driving school management platform with event sourcing (Verbs), media library (Spatie), real-time chat (SSE), and white-label theming.

**Architecture:** Flat domain folders under `app/`. Actions for business logic. Verbs for key domain events (hybrid). Spatie Media Library for files. SSE for chat. No service classes.

**Tech Stack:** Laravel 12, Inertia v2 (React 19), Fortify, MariaDB, Tailwind 4, Pest 4, Wayfinder, Verbs, Spatie Media Library

---

## Phase 0: Foundation

### Task 0.1: Add Role Enum and Migration

**Files:**
- Create: `app/Enums/UserRole.php`
- Create: `database/migrations/XXXX_add_role_to_users_table.php`
- Modify: `app/Models/User.php`
- Modify: `database/factories/UserFactory.php`
- Modify: `database/seeders/DatabaseSeeder.php`
- Test: `tests/Feature/Auth/RoleTest.php`

**Step 1: Create UserRole enum**

```php
// app/Enums/UserRole.php
<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Instructor = 'instructor';
    case Student = 'student';
}
```

**Step 2: Create migration**

Run: `php artisan make:migration add_role_to_users_table --table=users --no-interaction`

```php
public function up(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->string('role')->default('admin')->after('email');
    });
}

public function down(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropColumn('role');
    });
}
```

**Step 3: Update User model**

Add `'role'` to `$fillable`. Add to `casts()`: `'role' => UserRole::class`. Add helper methods:
```php
public function isAdmin(): bool { return $this->role === UserRole::Admin; }
public function isInstructor(): bool { return $this->role === UserRole::Instructor; }
public function isStudent(): bool { return $this->role === UserRole::Student; }
```

**Step 4: Update UserFactory**

Add `'role' => UserRole::Admin` to definition. Add states:
```php
public function instructor(): static
{
    return $this->state(fn () => ['role' => UserRole::Instructor]);
}

public function student(): static
{
    return $this->state(fn () => ['role' => UserRole::Student]);
}
```

**Step 5: Update seeder** — seed admin with `'role' => UserRole::Admin`

**Step 6: Write tests**

```php
// tests/Feature/Auth/RoleTest.php
test('user has admin role by default', function () {
    $user = User::factory()->create();
    expect($user->role)->toBe(UserRole::Admin);
    expect($user->isAdmin())->toBeTrue();
});

test('user can be created as instructor', function () {
    $user = User::factory()->instructor()->create();
    expect($user->isInstructor())->toBeTrue();
});

test('user can be created as student', function () {
    $user = User::factory()->student()->create();
    expect($user->isStudent())->toBeTrue();
});
```

**Step 7:** Run: `php artisan test --compact --filter=RoleTest`

**Step 8:** `vendor/bin/pint --dirty --format agent && git add -A && git commit -m "feat: add UserRole enum and role column to users"`

---

### Task 0.2: Role Middleware

**Files:**
- Create: `app/Http/Middleware/EnsureUserHasRole.php`
- Modify: `bootstrap/app.php`
- Test: `tests/Feature/Auth/RoleMiddlewareTest.php`

**Step 1: Create middleware**

```php
// app/Http/Middleware/EnsureUserHasRole.php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $userRole = $request->user()?->role;

        if (!$userRole || !in_array($userRole->value, $roles)) {
            abort(403);
        }

        return $next($request);
    }
}
```

**Step 2: Register in bootstrap/app.php**

```php
$middleware->alias([
    'role' => \App\Http\Middleware\EnsureUserHasRole::class,
]);
```

**Step 3: Write tests, run, commit**

Run: `php artisan test --compact --filter=RoleMiddlewareTest`
Commit: `feat: add role middleware for route protection`

---

### Task 0.3: All Enums

**Files:**
- Create: `app/Enums/StudentStatus.php`
- Create: `app/Enums/OfferType.php`
- Create: `app/Enums/BookingType.php`
- Create: `app/Enums/BookingStatus.php`
- Create: `app/Enums/PaymentMethod.php`
- Create: `app/Enums/ConversationType.php`
- Test: `tests/Unit/Enums/EnumTest.php`

```php
// app/Enums/StudentStatus.php
enum StudentStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Graduated = 'graduated';
    case DroppedOut = 'dropped_out';
}

// app/Enums/OfferType.php
enum OfferType: string
{
    case Primary = 'primary';
    case Addon = 'addon';
}

// app/Enums/BookingType.php
enum BookingType: string
{
    case DrivingLesson = 'driving_lesson';
    case TheoryLesson = 'theory_lesson';
    case TrackDriving = 'track_driving';
    case SlipperyDriving = 'slippery_driving';
    case Exam = 'exam';
}

// app/Enums/BookingStatus.php
enum BookingStatus: string
{
    case Scheduled = 'scheduled';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';
}

// app/Enums/PaymentMethod.php
enum PaymentMethod: string
{
    case Cash = 'cash';
    case Card = 'card';
    case MobilePay = 'mobile_pay';
    case Invoice = 'invoice';
}

// app/Enums/ConversationType.php
enum ConversationType: string
{
    case Direct = 'direct';
    case Group = 'group';
}
```

Test each enum has expected cases and values.

Commit: `feat: add all domain enums`

---

### Task 0.4: CPR Encryption Cast

**Files:**
- Create: `app/Casts/EncryptedCpr.php`
- Test: `tests/Unit/Casts/EncryptedCprTest.php`

Run: `php artisan make:cast EncryptedCpr --no-interaction`

Uses Laravel's `encrypt()`/`decrypt()` (AES-256-CBC with APP_KEY). Handles null gracefully.

Tests: encrypts on set, decrypts on get, handles null.

Commit: `feat: add EncryptedCpr cast`

---

### Task 0.5: Install and Configure Verbs

**Files:**
- Modify: `composer.json` (add thunk/verbs)
- Run Verbs install command
- Test: `tests/Feature/VerbsSetupTest.php`

**Step 1: Install Verbs**

Run: `composer require thunk/verbs --no-interaction`
Run: `php artisan verbs:install --no-interaction` (if available, otherwise run migrations manually)
Run: `php artisan migrate --no-interaction`

**Step 2: Verify setup with a smoke test**

Create a simple test event and state to verify Verbs works:

```php
// tests/Feature/VerbsSetupTest.php
test('verbs events can be fired and states loaded', function () {
    // This test just verifies the Verbs infrastructure works
    // Real events are tested in their respective phases
    expect(class_exists(\Thunk\Verbs\Event::class))->toBeTrue();
});
```

Commit: `feat: install and configure Verbs event sourcing`

---

### Task 0.6: Install and Configure Spatie Media Library

**Files:**
- Modify: `composer.json` (add spatie/laravel-medialibrary)
- Run migration publish

**Step 1: Install**

Run: `composer require spatie/laravel-medialibrary --no-interaction`
Run: `php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-migrations" --no-interaction`
Run: `php artisan migrate --no-interaction`

**Step 2: Verify with smoke test**

Commit: `feat: install Spatie Media Library`

---

### Task 0.7: Disable Public Registration

**Files:**
- Modify: `config/fortify.php` (remove Features::registration())
- Modify: `tests/Feature/Auth/RegistrationTest.php`

Comment out `Features::registration()`. Update test to verify `/register` returns 404.

Commit: `feat: disable public registration`

---

### Task 0.8: White-Label Theming

**Files:**
- Create: `config/branding.php`
- Create: `resources/js/components/theme-provider.tsx`
- Modify: `app/Http/Middleware/HandleInertiaRequests.php`
- Modify: `.env.example`
- Test: `tests/Feature/BrandingTest.php`

Config reads `BRAND_*` env vars. Middleware shares branding. ThemeProvider injects CSS variable overrides. App logo checks for custom logo.

Commit: `feat: add white-label theming system`

---

### Task 0.9: In-App Notifications Setup

**Files:**
- Run: `php artisan make:notifications-table --no-interaction && php artisan migrate --no-interaction`
- Modify: `app/Http/Middleware/HandleInertiaRequests.php` (share notifications)
- Create: `resources/js/components/notification-bell.tsx`
- Modify: `resources/js/components/app-sidebar.tsx` or `app-header.tsx`

Share via Inertia:
```php
'auth' => [
    'user' => $request->user(),
    'notifications' => $request->user()?->unreadNotifications()->latest()->take(10)->get(),
    'unread_count' => $request->user()?->unreadNotifications()->count() ?? 0,
],
```

Create NotificationBell component showing unread count badge. Clicking opens dropdown of recent notifications.

Commit: `feat: add in-app notification channel with bell UI`

---

## Phase 1: Students & Teams

### Task 1.1: Student Model, Migration, Factory

**Files:**
- Create: `app/Models/Student.php`
- Create: `database/migrations/XXXX_create_students_table.php`
- Create: `database/factories/StudentFactory.php`
- Modify: `app/Models/User.php` (add student() hasOne)
- Test: `tests/Feature/Students/StudentModelTest.php`

Run: `php artisan make:model Student -mf --no-interaction`

Migration:
```php
Schema::create('students', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
    $table->string('phone')->nullable();
    $table->text('cpr')->nullable();
    $table->string('status')->default('active');
    $table->date('start_date')->nullable();
    $table->softDeletes();
    $table->timestamps();
});
```

Student model: `HasFactory`, `SoftDeletes`, `HasMedia` (Spatie). Casts: `cpr` → `EncryptedCpr`, `status` → `StudentStatus`, `start_date` → `date`. Relations: `user()`, `bookings()`, `payments()`, `offers()`, `teams()`.

Register media collections:
```php
public function registerMediaCollections(): void
{
    $this->addMediaCollection('documents');
    $this->addMediaCollection('photos');
}
```

Factory uses `User::factory()->student()` for `user_id`. States: `inactive()`, `graduated()`.

Tests: user relation, CPR encryption, status cast, soft delete.

Commit: `feat: add Student model with encrypted CPR and Spatie Media`

---

### Task 1.2: Student Policy

**Files:**
- Create: `app/Policies/StudentPolicy.php`
- Test: `tests/Feature/Students/StudentPolicyTest.php`

Run: `php artisan make:policy StudentPolicy --model=Student --no-interaction`

Admin: full access. Instructor: viewAny/view. Student: view own only. Create/update/delete: admin only.

Commit: `feat: add StudentPolicy with role-based access`

---

### Task 1.3: Student CRUD Actions

**Files:**
- Create: `app/Actions/Students/CreateStudent.php`
- Create: `app/Actions/Students/UpdateStudent.php`
- Create: `app/Actions/Students/DeleteStudent.php`
- Test: `tests/Feature/Students/StudentActionsTest.php`

CreateStudent: DB transaction creating User (role=student) + Student. Fires `StudentEnrolled` Verbs event.
UpdateStudent: Updates student + user fields.
DeleteStudent: Soft deletes.

Commit: `feat: add Student CRUD actions`

---

### Task 1.4: Student Controller, Routes, FormRequests

**Files:**
- Create: `app/Http/Controllers/Students/StudentController.php`
- Create: `app/Http/Requests/Students/StoreStudentRequest.php`
- Create: `app/Http/Requests/Students/UpdateStudentRequest.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Students/StudentControllerTest.php`

Resource routes: `Route::resource('students', StudentController::class)->middleware(['auth', 'verified'])`

Controller: thin, calls actions, uses `$this->authorize()`.

Commit: `feat: add StudentController with CRUD routes`

---

### Task 1.5: Student Inertia Pages

**Files:**
- Create: `resources/js/pages/students/index.tsx`
- Create: `resources/js/pages/students/create.tsx`
- Create: `resources/js/pages/students/edit.tsx`
- Create: `resources/js/pages/students/show.tsx`
- Create: `resources/js/types/student.ts`
- Modify: `resources/js/types/index.ts`
- Modify: `resources/js/components/app-sidebar.tsx`

Follow existing page patterns. Index: paginated table. Create/Edit: form with Wayfinder. Show: detail view with media.

Run: `php artisan wayfinder:generate`

Commit: `feat: add Student Inertia pages`

---

### Task 1.6: Student Media Upload UI

**Files:**
- Create: `app/Http/Controllers/Students/StudentMediaController.php`
- Modify: `resources/js/pages/students/show.tsx` (add upload section)
- Modify: `routes/web.php`
- Test: `tests/Feature/Students/StudentMediaTest.php`

Controller handles upload (store to Spatie collection) and download (secure via policy check).

Routes:
```php
Route::post('students/{student}/media', [StudentMediaController::class, 'store']);
Route::get('students/{student}/media/{media}', [StudentMediaController::class, 'show']);
Route::delete('students/{student}/media/{media}', [StudentMediaController::class, 'destroy']);
```

Commit: `feat: add student media upload with Spatie`

---

### Task 1.7: Teams Model and CRUD

**Files:**
- Create: `app/Models/Team.php` with migration, factory
- Create: `database/migrations/XXXX_create_teams_table.php`
- Create: `database/migrations/XXXX_create_student_team_table.php`
- Create: `app/Http/Controllers/Teams/TeamController.php`
- Create: `app/Policies/TeamPolicy.php`
- Create: `resources/js/pages/teams/index.tsx`, `create.tsx`, `edit.tsx`, `show.tsx`
- Test: `tests/Feature/Teams/TeamTest.php`

Team model: `name`, `description`. BelongsToMany students. Admin-only CRUD.

Pivot: `student_team` (student_id, team_id).

Student model: add `teams()` belongsToMany.

Commit: `feat: add Teams with flexible student grouping`

---

### Task 1.8: Verbs Events for Students

**Files:**
- Create: `app/Events/StudentEnrolled.php`
- Create: `app/Events/StudentStatusChanged.php`
- Create: `app/States/StudentProgressionState.php`
- Modify: `app/Actions/Students/CreateStudent.php` (fire event)
- Modify: `app/Actions/Students/UpdateStudent.php` (fire event on status change)
- Test: `tests/Feature/Students/StudentEventsTest.php`

```php
// app/Events/StudentEnrolled.php
class StudentEnrolled extends \Thunk\Verbs\Event
{
    #[StateId(StudentProgressionState::class)]
    public int $student_id;

    public string $student_name;
    public string $start_date;

    public function apply(StudentProgressionState $state): void
    {
        $state->enrolled_at = $this->start_date;
        $state->lesson_counts = [];
    }
}
```

StudentProgressionState initialized on enrollment, updated by booking events later.

Commit: `feat: add Verbs events for student lifecycle`

---

## Phase 2: Vehicles & Offers

### Task 2.1: Vehicle Model and CRUD

**Files:**
- Create: `app/Models/Vehicle.php` with migration, factory
- Create: `app/Http/Controllers/Vehicles/VehicleController.php`
- Create: `app/Http/Requests/Vehicles/StoreVehicleRequest.php`
- Create: `app/Http/Requests/Vehicles/UpdateVehicleRequest.php`
- Create: `app/Policies/VehiclePolicy.php`
- Create: `resources/js/pages/vehicles/index.tsx`, `create.tsx`, `edit.tsx`
- Test: `tests/Feature/Vehicles/VehicleTest.php`

Simple Eloquent CRUD (no Verbs). Admin-only.

Commit: `feat: add Vehicle model with CRUD`

---

### Task 2.2: Offer Model and CRUD (was Package)

**Files:**
- Create: `app/Models/Offer.php` with migration, factory
- Create: `app/Http/Controllers/Offers/OfferController.php`
- Create: `app/Http/Requests/Offers/StoreOfferRequest.php`
- Create: `app/Http/Requests/Offers/UpdateOfferRequest.php`
- Create: `app/Policies/OfferPolicy.php`
- Create: `resources/js/pages/offers/index.tsx`, `create.tsx`, `edit.tsx`
- Test: `tests/Feature/Offers/OfferTest.php`

Migration:
```php
Schema::create('offers', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->text('description')->nullable();
    $table->decimal('price', 10, 2);
    $table->string('type')->default('primary');
    $table->integer('theory_lessons')->default(0);
    $table->integer('driving_lessons')->default(0);
    $table->boolean('track_required')->default(false);
    $table->boolean('slippery_required')->default(false);
    $table->timestamps();
});
```

Offer model: casts `type` → `OfferType`, `price` → `decimal:2`.

Commit: `feat: add Offer model (Schema.org) with CRUD`

---

### Task 2.3: Offer Assignment + Verbs Event

**Files:**
- Create: `database/migrations/XXXX_create_offer_student_table.php`
- Create: `app/Actions/Offers/AssignOffer.php`
- Create: `app/Events/OfferAssigned.php`
- Create: `app/States/StudentBalanceState.php`
- Modify: `app/Models/Student.php` (add offers relation)
- Modify: `app/Models/Offer.php` (add students relation)
- Test: `tests/Feature/Offers/AssignOfferTest.php`

Pivot: `offer_student` (offer_id, student_id, assigned_at).

AssignOffer action attaches pivot and fires OfferAssigned event.
OfferAssigned updates StudentBalanceState (adds to total_owed).

Commit: `feat: add offer assignment with Verbs event`

---

## Phase 3: Bookings

### Task 3.1: Booking Model

**Files:**
- Create: `app/Models/Booking.php` with migration, factory
- Test: `tests/Feature/Bookings/BookingModelTest.php`

Migration, relations (student, instructor/user, vehicle), enum casts, `scopeOverlapping()`.

Commit: `feat: add Booking model with relations and scopes`

---

### Task 3.2: Booking Conflict Detection

**Files:**
- Create: `app/Actions/Bookings/CheckBookingConflicts.php`
- Test: `tests/Feature/Bookings/BookingConflictTest.php`

Checks instructor, vehicle, and student conflicts using `scopeOverlapping()`. Returns array of conflict descriptions or empty.

Commit: `feat: add booking conflict detection action`

---

### Task 3.3: Booking CRUD + Verbs Events

**Files:**
- Create: `app/Actions/Bookings/CreateBooking.php`
- Create: `app/Actions/Bookings/UpdateBooking.php`
- Create: `app/Actions/Bookings/CancelBooking.php`
- Create: `app/Actions/Bookings/CompleteBooking.php`
- Create: `app/Events/BookingCreated.php`
- Create: `app/Events/BookingCompleted.php`
- Create: `app/Events/BookingCancelled.php`
- Create: `app/Http/Controllers/Bookings/BookingController.php`
- Create: `app/Http/Requests/Bookings/StoreBookingRequest.php`
- Create: `app/Policies/BookingPolicy.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Bookings/BookingControllerTest.php`

Actions fire Verbs events. BookingCompleted updates StudentProgressionState (increments lesson count by type).

Commit: `feat: add Booking CRUD with Verbs events`

---

### Task 3.4: Booking Inertia Pages (Calendar)

**Files:**
- Create: `resources/js/pages/bookings/index.tsx`
- Create: `resources/js/pages/bookings/create.tsx`
- Create: `resources/js/types/booking.ts`

Calendar library needed — confirm with user before adding. Bookings color-coded by type.

Commit: `feat: add booking calendar UI`

---

### Task 3.5: Booking Drag & Drop

**Files:**
- Modify: `app/Http/Controllers/Bookings/BookingController.php`
- Modify: `resources/js/pages/bookings/index.tsx`
- Test: `tests/Feature/Bookings/BookingDragDropTest.php`

PATCH endpoint re-runs conflict detection.

Commit: `feat: add drag-and-drop booking updates`

---

## Phase 4: Payments

### Task 4.1: Payment Model and CRUD

**Files:**
- Create: `app/Models/Payment.php` with migration, factory
- Create: `app/Actions/Payments/RecordPayment.php`
- Create: `app/Events/PaymentRecorded.php`
- Create: `app/Http/Controllers/Payments/PaymentController.php`
- Create: `app/Http/Requests/Payments/StorePaymentRequest.php`
- Create: `app/Policies/PaymentPolicy.php`
- Create: `resources/js/pages/payments/index.tsx`, `create.tsx`
- Test: `tests/Feature/Payments/PaymentTest.php`

RecordPayment fires PaymentRecorded event → updates StudentBalanceState.

Commit: `feat: add Payment CRUD with Verbs balance tracking`

---

### Task 4.2: Balance Calculation via Verbs State

**Files:**
- Modify: `app/States/StudentBalanceState.php`
- Create: `app/Actions/Payments/CalculateBalance.php`
- Test: `tests/Feature/Payments/BalanceTest.php`

CalculateBalance loads StudentBalanceState and returns: total_owed, total_paid, outstanding.

Tests: single offer + full payment = 0 balance. Multiple offers + partial = correct outstanding. Addons included.

Commit: `feat: add balance calculation via Verbs state`

---

## Phase 5: Progression

### Task 5.1: StudentProgressionState

**Files:**
- Modify: `app/States/StudentProgressionState.php`
- Modify: `app/Events/BookingCompleted.php` (apply to state)
- Modify: `app/Events/BookingCancelled.php` (apply to state)
- Test: `tests/Feature/Progression/ProgressionStateTest.php`

State tracks: `lesson_counts` (array keyed by BookingType), `enrolled_at`.

BookingCompleted increments count. BookingCancelled decrements if needed.

Commit: `feat: implement StudentProgressionState from booking events`

---

### Task 5.2: Exam Readiness

**Files:**
- Create: `app/Actions/Progression/CheckExamReadiness.php`
- Create: `resources/js/pages/progression/show.tsx`
- Create: `app/Http/Controllers/Progression/ProgressionController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Progression/ExamReadinessTest.php`

Loads StudentProgressionState + student's offers. Compares completed counts against requirements. Returns `{ is_ready, missing, completed }`.

Commit: `feat: add exam readiness tracking`

---

## Phase 6: Dashboard

### Task 6.1: KPI Actions and Dashboard

**Files:**
- Create: `app/Actions/Dashboard/CalculateKpis.php`
- Modify: `routes/web.php` (update dashboard route to use controller)
- Create: `app/Http/Controllers/DashboardController.php`
- Modify: `resources/js/pages/dashboard.tsx`
- Test: `tests/Feature/Dashboard/KpiTest.php`

KPIs: total_students (active), upcoming_bookings (7 days), no_show_rate, total_outstanding_balance.

Role-specific dashboard: admin sees all KPIs, instructor sees own bookings, student sees own progress.

Commit: `feat: add KPI dashboard`

---

## Phase 7: Blog

### Task 7.1: Blog Model and CRUD

**Files:**
- Create: `app/Models/BlogPost.php` with migration, factory (includes HasMedia)
- Create: `app/Http/Controllers/Blog/BlogPostController.php`
- Create: `app/Http/Requests/Blog/StoreBlogPostRequest.php`
- Create: `app/Http/Requests/Blog/UpdateBlogPostRequest.php`
- Create: `app/Policies/BlogPostPolicy.php`
- Create: `resources/js/pages/blog/index.tsx`, `create.tsx`, `edit.tsx`, `show.tsx`
- Test: `tests/Feature/Blog/BlogPostTest.php`

BlogPost model uses Spatie Media (collection: `featured` for featured images). Slug auto-generated via `Str::slug()`. Public show route (no auth). Admin-only CRUD.

Commit: `feat: add BlogPost with Spatie Media and public route`

---

## Phase 8: Reminders & Notifications

### Task 8.1: Booking Reminder Job

**Files:**
- Create: `app/Jobs/SendBookingReminder.php`
- Create: `app/Notifications/BookingReminderNotification.php`
- Modify: `routes/console.php`
- Test: `tests/Feature/Reminders/BookingReminderTest.php`

SendBookingReminder: finds bookings 23-25h away, sends notification via `mail` + `database` channels.

Schedule: `Schedule::job(new SendBookingReminder)->hourly()`

Commit: `feat: add booking reminder job`

---

### Task 8.2: No-Show Auto-Flag

**Files:**
- Create: `app/Jobs/FlagNoShows.php`
- Create: `app/Events/BookingNoShow.php`
- Modify: `routes/console.php`
- Test: `tests/Feature/Reminders/NoShowTest.php`

FlagNoShows: finds past bookings still `scheduled`, fires BookingNoShow Verbs event, updates status.

Schedule: `Schedule::job(new FlagNoShows)->dailyAt('02:00')`

Commit: `feat: add no-show auto-flag job with Verbs event`

---

### Task 8.3: Notification Types

**Files:**
- Create: `app/Notifications/BookingCancelledNotification.php`
- Create: `app/Notifications/PaymentReceivedNotification.php`
- Create: `app/Notifications/NewMessageNotification.php`
- Modify actions to dispatch notifications where appropriate
- Test: `tests/Feature/Notifications/NotificationTest.php`

All use `mail` + `database` channels. Test that notifications are sent and stored.

Commit: `feat: add notification types for bookings, payments, messages`

---

## Phase 9: Chat

### Task 9.1: Conversation and Message Models

**Files:**
- Create: `app/Models/Conversation.php` with migration, factory
- Create: `app/Models/Message.php` with migration, factory
- Create: `database/migrations/XXXX_create_conversations_table.php`
- Create: `database/migrations/XXXX_create_conversation_user_table.php`
- Create: `database/migrations/XXXX_create_messages_table.php`
- Test: `tests/Feature/Chat/ChatModelTest.php`

Conversation: `type` (ConversationType), `team_id` (nullable), `name` (nullable). Pivot: `conversation_user` (conversation_id, user_id, last_read_at).

Message: `conversation_id`, `user_id`, `body`.

Commit: `feat: add Conversation and Message models`

---

### Task 9.2: Chat Controller with SSE

**Files:**
- Create: `app/Http/Controllers/Chat/ConversationController.php`
- Create: `app/Http/Controllers/Chat/MessageController.php`
- Create: `app/Policies/ConversationPolicy.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Chat/ChatControllerTest.php`

ConversationController: list conversations, create DM, create team group chat.
MessageController: list messages for conversation, send message (POST), SSE stream for new messages (GET with `response()->eventStream()`).

SSE endpoint streams new messages for a conversation. Frontend uses `@laravel/stream-react`'s `useEventStream`.

Alternative: polling endpoint if SSE proves complex. Controller returns messages since `?after=timestamp`.

Install: `npm install @laravel/stream-react`

Commit: `feat: add chat controller with SSE streaming`

---

### Task 9.3: Chat UI

**Files:**
- Create: `resources/js/pages/chat/index.tsx` (conversation list + active thread)
- Create: `resources/js/components/chat/conversation-list.tsx`
- Create: `resources/js/components/chat/message-thread.tsx`
- Create: `resources/js/components/chat/message-input.tsx`
- Create: `resources/js/types/chat.ts`
- Modify: `resources/js/components/app-sidebar.tsx` (add Chat nav item)

Conversation list: shows all user's conversations with last message preview + unread indicator.
Message thread: scrollable message list with SSE-driven real-time updates.
Message input: text input with send button.

Commit: `feat: add chat UI with real-time message streaming`

---

## Summary

| Phase | Tasks | New Dependencies |
|-------|-------|-----------------|
| 0 | 0.1–0.9 | thunk/verbs, spatie/laravel-medialibrary |
| 1 | 1.1–1.8 | — |
| 2 | 2.1–2.3 | — |
| 3 | 3.1–3.5 | Calendar library (TBD) |
| 4 | 4.1–4.2 | — |
| 5 | 5.1–5.2 | — |
| 6 | 6.1 | — |
| 7 | 7.1 | — |
| 8 | 8.1–8.3 | — |
| 9 | 9.1–9.3 | @laravel/stream-react |
