<?php

namespace App\Http\Controllers\Students;

use App\Actions\Students\SendStudentLoginLink;
use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;

class StudentLoginLinkController extends Controller
{
    public function __invoke(Student $student, SendStudentLoginLink $action): RedirectResponse
    {
        $this->authorize('update', $student);

        $student->load('user');
        $action->handle($student);

        return back()->with('success', 'Login link sendt til '.$student->user->name.'.');
    }
}
