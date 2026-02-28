# Enrollment Wizard Design

**Goal:** Redesign `enroll.tsx` as a 3-step wizard — calendar date picker, student info, payment + submit.

---

## Backend change

`EnrollmentController@show` replaces `availableDates` + `courses` with a single `courseEvents` array:

```php
'courseEvents' => $courses->map(fn ($c) => [
    'id' => $c->id,
    'title' => $offer->name,
    'start' => $c->start_at->format('Y-m-d H:i'),
    'end' => $c->end_at->format('Y-m-d H:i'),
])->values(),
```

`StoreEnrollmentRequest` is unchanged — still validates `course_id`, name, email, etc.

---

## Wizard steps

### Step 1 — Calendar
- @schedule-x calendar (already installed), month view only, no week view
- Events from `courseEvents` prop
- Clicking an event selects it (highlight) and enables "Videre →"
- Selected course stored as `selectedCourse: { id, title, start, end } | null`

### Step 2 — Student info
- Fields: name, email, phone (optional), CPR (optional), password, password_confirmation
- "← Tilbage" returns to step 1, "Videre →" advances to step 3

### Step 3 — Payment + submit
- Payment method selector (Stripe / Cash), same UI as current enroll.tsx
- Summary card: selected course name + formatted date/time
- Submit via `router.post(store(offer.id).url, { course_id, name, email, phone, cpr, password, password_confirmation, payment_method })`
- On validation error: display errors and stay on step 3 (field errors shown inline); if `course_id` error, jump to step 1

---

## State management

Pure React `useState` — no Inertia form object, no page reloads between steps.

```ts
const [step, setStep] = useState<1 | 2 | 3>(1);
const [selectedCourse, setSelectedCourse] = useState<CourseEvent | null>(null);
const [fields, setFields] = useState({ name: '', email: '', phone: '', cpr: '', password: '', password_confirmation: '' });
const [paymentMethod, setPaymentMethod] = useState<'stripe' | 'cash'>('stripe');
const [errors, setErrors] = useState<Record<string, string>>({});
const [processing, setProcessing] = useState(false);
```

Submit uses `router.post(...)` with `onError` callback to capture errors and `onSuccess` for redirect.

---

## Step indicator

Simple numbered row at top of form area:

```
① Dato  ②  Info  ③ Betaling
```

Current step is bold/primary, completed steps are muted with a checkmark.

---

## Not in scope
- Week view on enrollment calendar
- Multi-date selection
- Editing selection after submission
