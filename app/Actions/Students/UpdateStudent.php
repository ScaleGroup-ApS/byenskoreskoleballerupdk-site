<?php

namespace App\Actions\Students;

use App\Events\StudentStatusChanged;
use App\Models\Student;

class UpdateStudent
{
    /**
     * @param  array{name?: string, email?: string, phone?: string|null, cpr?: string|null, status?: string, start_date?: string|null}  $data
     */
    public function handle(Student $student, array $data): Student
    {
        $oldStatus = $student->status?->value;

        $student->phone = $data['phone'] ?? $student->phone;
        $student->cpr = $data['cpr'] ?? $student->cpr;
        $student->status = $data['status'] ?? $student->status;
        $student->start_date = $data['start_date'] ?? $student->start_date;
        $student->save();

        $newStatus = $student->status?->value;

        if ($oldStatus !== $newStatus && $newStatus !== null) {
            StudentStatusChanged::fire(
                student_id: $student->id,
                old_status: $oldStatus ?? 'unknown',
                new_status: $newStatus,
            );
        }

        $student->user->name = $data['name'] ?? $student->user->name;
        $student->user->email = $data['email'] ?? $student->user->email;
        $student->user->save();

        return $student->refresh();
    }
}
