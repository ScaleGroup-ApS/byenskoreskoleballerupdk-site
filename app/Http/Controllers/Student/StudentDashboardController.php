<?php

namespace App\Http\Controllers\Student;

use App\Actions\Payments\CalculateBalance;
use App\Actions\Progression\CheckExamReadiness;
use App\Enums\BookingStatus;
use App\Enums\EnrollmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentDashboardController extends Controller
{
    public function __invoke(Request $request, CheckExamReadiness $readiness, CalculateBalance $balance): Response
    {
        $student = $request->user()->student;

        abort_unless($student, 404);

        $student->load(['offers' => fn ($q) => $q->with('media')]);

        $booking = Booking::query()
            ->where('student_id', $student->id)
            ->where('starts_at', '>=', now())
            ->whereNotIn('status', [BookingStatus::Cancelled->value, BookingStatus::NoShow->value])
            ->orderBy('starts_at')
            ->first();

        $enrollment = Enrollment::query()
            ->where('student_id', $student->id)
            ->whereIn('status', [EnrollmentStatus::Completed->value, EnrollmentStatus::PendingApproval->value])
            ->with(['course', 'offer'])
            ->whereHas('course', fn ($q) => $q->where('start_at', '>=', now()))
            ->first();

        $nextLesson = null;

        if ($booking && $enrollment?->course) {
            $nextLesson = $booking->starts_at->lte($enrollment->course->start_at)
                ? ['type' => $booking->type->value, 'starts_at' => $booking->starts_at->toIso8601String(), 'ends_at' => $booking->ends_at->toIso8601String()]
                : ['type' => $enrollment->offer->name, 'starts_at' => $enrollment->course->start_at->toIso8601String(), 'ends_at' => $enrollment->course->end_at->toIso8601String()];
        } elseif ($booking) {
            $nextLesson = ['type' => $booking->type->value, 'starts_at' => $booking->starts_at->toIso8601String(), 'ends_at' => $booking->ends_at->toIso8601String()];
        } elseif ($enrollment?->course) {
            $nextLesson = ['type' => $enrollment->offer->name, 'starts_at' => $enrollment->course->start_at->toIso8601String(), 'ends_at' => $enrollment->course->end_at->toIso8601String()];
        }

        $pendingEnrollment = Enrollment::query()
            ->where('student_id', $student->id)
            ->whereIn('status', [EnrollmentStatus::PendingPayment->value, EnrollmentStatus::PendingApproval->value])
            ->with('offer')
            ->first();

        $materials = $student->offers->flatMap(fn ($offer) => $offer->getMedia('materials')->map(fn ($media) => [
            'id' => $media->id,
            'name' => $media->name,
            'file_name' => $media->file_name,
            'mime_type' => $media->mime_type,
            'size' => $media->human_readable_size,
            'url' => route('student.offers.materials.show', [$offer->id, $media->id]),
            'offer_name' => $offer->name,
        ])
        )->values()->all();

        return Inertia::render('student/index', [
            'pendingEnrollment' => $pendingEnrollment ? [
                'status' => $pendingEnrollment->status->value,
                'payment_method' => $pendingEnrollment->payment_method->value,
                'offer_price' => (float) $pendingEnrollment->offer->price,
            ] : null,
            'booking' => $nextLesson,
            'readiness' => $readiness->handle($student),
            'balance' => $balance->handle($student),
            'materials' => $materials,
        ]);
    }
}
