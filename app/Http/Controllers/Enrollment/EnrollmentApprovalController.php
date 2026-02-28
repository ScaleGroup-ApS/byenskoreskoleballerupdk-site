<?php

namespace App\Http\Controllers\Enrollment;

use App\Actions\Enrollment\ApproveEnrollment;
use App\Actions\Enrollment\RejectEnrollment;
use App\Enums\EnrollmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Enrollment\RejectEnrollmentRequest;
use App\Models\Enrollment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EnrollmentApprovalController extends Controller
{
    public function index(): Response
    {
        $enrollments = Enrollment::query()
            ->whereIn('status', [EnrollmentStatus::PendingApproval, EnrollmentStatus::PendingPayment])
            ->with(['student.user', 'offer'])
            ->latest()
            ->get();

        return Inertia::render('enrollments/index', [
            'enrollments' => $enrollments->map(fn (Enrollment $enrollment) => [
                'id' => $enrollment->id,
                'student_name' => $enrollment->student->user->name,
                'student_email' => $enrollment->student->user->email,
                'offer_name' => $enrollment->offer->name,
                'payment_method' => $enrollment->payment_method->value,
                'status' => $enrollment->status->value,
                'created_at' => $enrollment->created_at->toISOString(),
            ]),
        ]);
    }

    public function approve(Request $request, Enrollment $enrollment, ApproveEnrollment $approveEnrollment): RedirectResponse
    {
        $approveEnrollment->handle($enrollment, $request->user());

        return redirect()->route('enrollments.index')->with('success', 'Tilmelding godkendt.');
    }

    public function reject(RejectEnrollmentRequest $request, Enrollment $enrollment, RejectEnrollment $rejectEnrollment): RedirectResponse
    {
        $rejectEnrollment->handle($enrollment, $request->validated('rejection_reason'));

        return redirect()->route('enrollments.index')->with('success', 'Tilmelding afvist.');
    }
}
