<?php

namespace App\Http\Controllers\Courses;

use App\Http\Controllers\Controller;
use App\Http\Requests\Courses\UpdateCourseRequest;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    public function index(): Response
    {
        $courses = Course::query()
            ->with('offer')
            ->withCount('enrollments')
            ->upcoming()
            ->orderBy('start_at')
            ->get()
            ->map(fn (Course $course) => [
                'id' => $course->id,
                'start_at' => $course->start_at->toIso8601String(),
                'end_at' => $course->end_at->toIso8601String(),
                'offer' => ['id' => $course->offer->id, 'name' => $course->offer->name],
                'enrollments_count' => $course->enrollments_count,
                'max_students' => $course->max_students,
            ]);

        return Inertia::render('courses/index', ['courses' => $courses]);
    }

    public function show(Course $course): Response
    {
        $course->load(['offer', 'enrollments.student.user']);

        return Inertia::render('courses/show', [
            'course' => [
                'id' => $course->id,
                'start_at' => $course->start_at->toIso8601String(),
                'end_at' => $course->end_at->toIso8601String(),
                'max_students' => $course->max_students,
                'offer' => [
                    'id' => $course->offer->id,
                    'name' => $course->offer->name,
                ],
                'enrollments' => $course->enrollments->map(fn ($enrollment) => [
                    'id' => $enrollment->id,
                    'status' => $enrollment->status->value,
                    'payment_method' => $enrollment->payment_method->value,
                    'student' => [
                        'id' => $enrollment->student->id,
                        'name' => $enrollment->student->user->name,
                        'email' => $enrollment->student->user->email,
                    ],
                ]),
            ],
        ]);
    }

    public function update(UpdateCourseRequest $request, Course $course): RedirectResponse
    {
        $course->update($request->validated());

        return redirect()->route('courses.show', $course)
            ->with('success', 'Kursus opdateret.');
    }
}
