<?php

namespace App\Http\Controllers;

use App\Actions\Dashboard\CalculateKpis;
use App\Enums\EnrollmentStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Offer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, CalculateKpis $kpis): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user->isStudent()) {
            return redirect()->route('student.dashboard');
        }

        $enrollments = Enrollment::query()
            ->when($user->isInstructor(), function ($query) use ($user) {
                $query->whereIn('student_id', function ($subQuery) use ($user) {
                    $subQuery->select('student_id')
                        ->from('bookings')
                        ->where('instructor_id', $user->id)
                        ->distinct();
                });
            })
            ->whereIn('status', [EnrollmentStatus::PendingApproval, EnrollmentStatus::PendingPayment])
            ->with(['student.user', 'offer'])
            ->latest()
            ->get()
            ->map(fn ($enrollment) => [
                'id' => $enrollment->id,
                'student_name' => $enrollment->student->user->name,
                'student_email' => $enrollment->student->user->email,
                'offer_name' => $enrollment->offer->name,
                'payment_method' => $enrollment->payment_method->value,
                'status' => $enrollment->status->value,
                'created_at' => $enrollment->created_at->toISOString(),
            ]);

        $courses = Course::query()
            ->with('offer:id,name')
            ->orderBy('start_at')
            ->get()
            ->map(fn (Course $course) => [
                'id' => $course->id,
                'title' => $course->offer->name,
                'start' => $course->start_at->toIso8601String(),
                'end' => $course->end_at->toIso8601String(),
            ])
            ->all();

        $offers = Offer::query()->orderBy('type')->orderBy('name')->get(['id', 'name'])->all();

        return Inertia::render('dashboard', [
            'kpis' => $kpis->handle($user),
            'courses' => $courses,
            'enrollments' => $enrollments,
            'offers' => $offers,
        ]);
    }
}
