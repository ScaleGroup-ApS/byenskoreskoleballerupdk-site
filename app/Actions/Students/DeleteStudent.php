<?php

namespace App\Actions\Students;

use App\Events\StudentDeleted;
use App\Models\Student;

class DeleteStudent
{
    public function handle(Student $student): void
    {
        $studentId = $student->id;
        $studentName = $student->user->name;

        $student->delete();

        StudentDeleted::fire(
            student_id: $studentId,
            student_name: $studentName,
        );
    }
}
