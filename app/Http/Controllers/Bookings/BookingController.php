<?php

namespace App\Http\Controllers\Bookings;

use App\Actions\Bookings\CancelBooking;
use App\Actions\Bookings\CheckBookingConflicts;
use App\Actions\Bookings\CompleteBooking;
use App\Actions\Bookings\CreateBooking;
use App\Actions\Bookings\UpdateBooking;
use App\Enums\BookingStatus;
use App\Enums\BookingType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Bookings\StoreBookingRequest;
use App\Http\Requests\Bookings\UpdateBookingRequest;
use App\Models\Booking;
use App\Models\Student;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Booking::class);

        $bookings = Booking::with(['student.user', 'instructor', 'vehicle'])
            ->orderBy('starts_at')
            ->get()
            ->map(fn (Booking $booking) => [
                'id' => $booking->id,
                'title' => $booking->student->user->name,
                'start' => $booking->starts_at->toIso8601String(),
                'end' => $booking->ends_at->toIso8601String(),
                'type' => $booking->type->value,
                'status' => $booking->status->value,
                'instructor' => $booking->instructor?->name,
                'vehicle' => $booking->vehicle?->name,
                'notes' => $booking->notes,
            ]);

        return Inertia::render('bookings/index', [
            'bookings' => $bookings,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Booking::class);

        return Inertia::render('bookings/create', [
            'students' => Student::with('user')->get(),
            'instructors' => User::query()->where('role', 'instructor')->orWhere('role', 'admin')->get(),
            'vehicles' => Vehicle::query()->where('active', true)->get(),
            'bookingTypes' => collect(BookingType::cases())->map(fn ($t) => [
                'value' => $t->value,
                'label' => $t->name,
            ]),
        ]);
    }

    public function store(
        StoreBookingRequest $request,
        CheckBookingConflicts $conflictChecker,
        CreateBooking $action,
    ): RedirectResponse {
        $data = $request->validated();

        $student = Student::findOrFail($data['student_id']);
        $instructor = User::findOrFail($data['instructor_id']);
        $vehicle = isset($data['vehicle_id']) ? Vehicle::find($data['vehicle_id']) : null;

        $conflicts = $conflictChecker->handle(
            startsAt: $data['starts_at'],
            endsAt: $data['ends_at'],
            instructor: $instructor,
            student: $student,
            vehicle: $vehicle,
        );

        if (! empty($conflicts)) {
            return back()->withErrors(['conflicts' => $conflicts]);
        }

        $action->handle($data);

        return redirect()->route('bookings.index')
            ->with('success', 'Booking oprettet.');
    }

    public function update(
        UpdateBookingRequest $request,
        Booking $booking,
        CheckBookingConflicts $conflictChecker,
        UpdateBooking $updateAction,
        CompleteBooking $completeAction,
        CancelBooking $cancelAction,
    ): RedirectResponse {
        $this->authorize('update', $booking);

        $data = $request->validated();

        if (isset($data['status'])) {
            $newStatus = BookingStatus::from($data['status']);

            if ($newStatus === BookingStatus::Completed) {
                $completeAction->handle($booking);
            } elseif ($newStatus === BookingStatus::Cancelled) {
                $cancelAction->handle($booking);
            }

            return back()->with('success', 'Booking opdateret.');
        }

        $startsAt = $data['starts_at'] ?? $booking->starts_at->toDateTimeString();
        $endsAt = $data['ends_at'] ?? $booking->ends_at->toDateTimeString();

        $instructor = array_key_exists('instructor_id', $data)
            ? ($data['instructor_id'] ? User::find($data['instructor_id']) : null)
            : $booking->instructor;

        $vehicle = array_key_exists('vehicle_id', $data)
            ? ($data['vehicle_id'] ? Vehicle::find($data['vehicle_id']) : null)
            : $booking->vehicle;

        $conflicts = $conflictChecker->handle(
            startsAt: $startsAt,
            endsAt: $endsAt,
            instructor: $instructor,
            student: $booking->student,
            vehicle: $vehicle,
            excludeBookingId: $booking->id,
        );

        if (! empty($conflicts)) {
            return back()->withErrors(['conflicts' => $conflicts]);
        }

        $updateAction->handle($booking, $data);

        return back()->with('success', 'Booking opdateret.');
    }

    public function destroy(Booking $booking, CancelBooking $action): RedirectResponse
    {
        $this->authorize('delete', $booking);

        $action->handle($booking);

        return redirect()->route('bookings.index')
            ->with('success', 'Booking annulleret.');
    }
}
