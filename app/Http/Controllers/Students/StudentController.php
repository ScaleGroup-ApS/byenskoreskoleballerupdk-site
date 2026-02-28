<?php

namespace App\Http\Controllers\Students;

use App\Actions\Students\CreateStudent;
use App\Actions\Students\DeleteStudent;
use App\Actions\Students\UpdateStudent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Students\StoreStudentRequest;
use App\Http\Requests\Students\UpdateStudentRequest;
use App\Http\Resources\StudentResource;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Thunk\Verbs\Models\VerbEvent;

class StudentController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Student::class);

        $students = Student::with('user')
            ->latest()
            ->paginate(15);

        return Inertia::render('students/index', [
            'students' => StudentResource::collection($students),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Student::class);

        return Inertia::render('students/create');
    }

    public function store(StoreStudentRequest $request, CreateStudent $action): RedirectResponse
    {
        $student = $action->handle($request->validated());

        return redirect()->route('students.show', $student)
            ->with('success', 'Elev oprettet.');
    }

    public function show(Request $request, Student $student): Response
    {
        $this->authorize('view', $student);

        $student->load('user', 'media');

        $canEdit = $request->user()->isAdmin();

        return Inertia::render('students/show', [
            'student' => $student,
            'canEdit' => $canEdit,
            'eventTimeline' => $canEdit
                ? VerbEvent::query()
                    ->where('data->student_id', $student->id)
                    ->latest()
                    ->get()
                    ->map(fn (VerbEvent $event) => [
                        'id' => (string) $event->id,
                        'summary' => $this->eventSummary($event),
                        'category' => $this->eventCategory($event),
                        'created_at' => $event->created_at->toISOString(),
                    ])
                : [],
        ]);
    }

    public function edit(Student $student): Response
    {
        $this->authorize('update', $student);

        $student->load('user');

        return Inertia::render('students/edit', [
            'student' => $student,
        ]);
    }

    public function update(UpdateStudentRequest $request, Student $student, UpdateStudent $action): RedirectResponse
    {
        $action->handle($student, $request->validated());

        return redirect()->route('students.show', $student)
            ->with('success', 'Elev opdateret.');
    }

    public function destroy(Student $student, DeleteStudent $action): RedirectResponse
    {
        $this->authorize('delete', $student);

        $action->handle($student);

        return redirect()->route('students.index')
            ->with('success', 'Elev slettet.');
    }

    private function eventSummary(VerbEvent $event): string
    {
        return match (class_basename($event->type)) {
            'BookingCreated' => 'Booking oprettet',
            'BookingCompleted' => 'Booking gennemført',
            'BookingCancelled' => 'Booking annulleret',
            'BookingNoShow' => 'Elev mødte ikke op',
            'EnrollmentRequested' => 'Tilmelding anmodet',
            'EnrollmentApproved' => 'Tilmelding godkendt',
            'EnrollmentRejected' => 'Tilmelding afvist',
            'StudentEnrolled' => 'Elev tilmeldt',
            'StudentStatusChanged' => 'Status ændret → '.($event->data['new_status'] ?? ''),
            'OfferAssigned' => 'Tilbud tildelt: '.($event->data['offer_name'] ?? ''),
            'PaymentRecorded' => 'Betaling registreret: '.number_format((float) ($event->data['amount'] ?? 0), 2, ',', '.').' kr.',
            'StripePaymentCompleted' => 'Stripe-betaling gennemført',
            default => class_basename($event->type),
        };
    }

    private function eventCategory(VerbEvent $event): string
    {
        return match (class_basename($event->type)) {
            'BookingCreated', 'BookingCompleted', 'BookingCancelled', 'BookingNoShow' => 'booking',
            'EnrollmentRequested', 'EnrollmentApproved', 'EnrollmentRejected' => 'enrollment',
            'StudentEnrolled', 'StudentStatusChanged' => 'student',
            'OfferAssigned', 'PaymentRecorded', 'StripePaymentCompleted' => 'payment',
            default => 'other',
        };
    }
}
