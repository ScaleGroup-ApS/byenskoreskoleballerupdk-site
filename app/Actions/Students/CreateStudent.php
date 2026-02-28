<?php

namespace App\Actions\Students;

use App\Enums\StudentStatus;
use App\Enums\UserRole;
use App\Events\StudentEnrolled;
use App\Models\Student;
use App\Models\User;
use App\Mail\StudentMagicLoginMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use MagicLink\Actions\LoginAction;
use MagicLink\MagicLink;
use Illuminate\Support\Str;

class CreateStudent
{
    /**
     * @param  array{name: string, email: string, phone?: string, cpr?: string, start_date?: string}  $data
     */
    public function handle(array $data): Student
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => null,
                'role' => UserRole::Student,
            ]);

            $student = Student::create([
                'user_id' => $user->id,
                'phone' => $data['phone'] ?? null,
                'cpr' => $data['cpr'] ?? null,
                'status' => StudentStatus::Active,
                'start_date' => $data['start_date'] ?? now()->toDateString(),
            ]);

            StudentEnrolled::fire(
                student_id: $student->id,
                student_name: $user->name,
                start_date: $student->start_date->toDateString(),
            );

            $action = new LoginAction($user, redirect()->route('student.dashboard'));
            $url = MagicLink::create($action)->url;
            
            Mail::to($user)->send(
                new StudentMagicLoginMail($user->name, $url)
            );

            return $student;
        });
    }
}
