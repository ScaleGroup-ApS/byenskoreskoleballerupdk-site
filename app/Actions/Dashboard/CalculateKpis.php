<?php

namespace App\Actions\Dashboard;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class CalculateKpis
{
    /**
     * @return array<string, mixed>
     */
    public function handle(User $user): array
    {
        if ($user->isAdmin()) {
            return $this->adminKpis();
        }

        if ($user->isInstructor()) {
            return $this->instructorKpis($user);
        }

        return [];
    }

    /**
     * @return array<string, mixed>
     */
    private function adminKpis(): array
    {
        $row = DB::table('admin_kpis_view')->first();

        return [
            'total_students' => (int) ($row->total_students ?? 0),
            'upcoming_bookings' => (int) ($row->upcoming_bookings ?? 0),
            'no_show_rate' => (float) ($row->no_show_rate ?? 0.0),
            'total_outstanding' => (float) ($row->total_outstanding ?? 0.0),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function instructorKpis(User $instructor): array
    {
        $row = DB::table('instructor_kpis_view')
            ->where('instructor_id', $instructor->id)
            ->first();

        return [
            'upcoming_bookings' => (int) ($row->upcoming_bookings ?? 0),
            'no_show_rate' => (float) ($row->no_show_rate ?? 0.0),
        ];
    }
}
