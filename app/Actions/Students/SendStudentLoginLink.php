<?php

namespace App\Actions\Students;

use App\Events\StudentLoginLinkSent;
use App\Mail\StudentMagicLoginMail;
use App\Models\Student;
use Illuminate\Support\Facades\Mail;
use MagicLink\Actions\LoginAction;
use MagicLink\MagicLink;

class SendStudentLoginLink
{
    public function handle(Student $student): void
    {
        $user = $student->user;

        $action = new LoginAction($user, redirect()->route('student.dashboard'));
        $url = MagicLink::create($action)->url;

        Mail::to($user)->send(
            new StudentMagicLoginMail($user->name, $url)
        );

        StudentLoginLinkSent::fire(
            student_id: $student->id,
            student_name: $user->name,
        );
    }
}
