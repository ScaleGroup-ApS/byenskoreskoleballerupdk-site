<?php

namespace App\Http\Controllers\Bookings;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BookingDayController extends Controller
{
    public function __invoke(Request $request, string $date): Response
    {
        $this->authorize('viewAny', Booking::class);

        $bookings = Booking::with(['student.user', 'instructor', 'vehicle', 'team'])
            ->whereDate('starts_at', $date)
            ->orderBy('starts_at')
            ->get();

        $individualEvents = $bookings
            ->whereNull('team_id')
            ->map(fn (Booking $booking) => [
                'id' => 'booking-'.$booking->id,
                'booking_id' => $booking->id,
                'title' => $booking->student->user->name,
                'start' => $booking->starts_at->toIso8601String(),
                'end' => $booking->ends_at->toIso8601String(),
                'type' => $booking->type->value,
                'status' => $booking->status->value,
                'team_id' => null,
                'instructor_id' => $booking->instructor_id,
                'instructor' => $booking->instructor?->name,
                'vehicle_id' => $booking->vehicle_id,
                'vehicle' => $booking->vehicle?->name,
                'notes' => $booking->notes,
            ]);

        $teamEvents = $bookings
            ->whereNotNull('team_id')
            ->groupBy(fn (Booking $b) => $b->team_id.'_'.$b->starts_at->toIso8601String())
            ->map(fn ($group) => $group->first())
            ->map(fn (Booking $booking) => [
                'id' => 'team-'.$booking->team_id.'-'.$booking->starts_at->timestamp,
                'booking_id' => null,
                'title' => $booking->team->name,
                'start' => $booking->starts_at->toIso8601String(),
                'end' => $booking->ends_at->toIso8601String(),
                'type' => $booking->type->value,
                'status' => $booking->status->value,
                'team_id' => $booking->team_id,
                'instructor_id' => $booking->instructor_id,
                'instructor' => $booking->instructor?->name,
                'vehicle_id' => $booking->vehicle_id,
                'vehicle' => $booking->vehicle?->name,
                'notes' => $booking->notes,
            ]);

        $instructors = User::query()
            ->where(fn ($q) => $q->where('role', 'instructor')->orWhere('role', 'admin'))
            ->orderBy('name')
            ->get(['id', 'name']);

        $vehicles = Vehicle::query()
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('bookings/day', [
            'date' => $date,
            'events' => collect($individualEvents->values())->merge(collect($teamEvents->values()))->values()->all(),
            'instructors' => $instructors,
            'vehicles' => $vehicles,
        ]);
    }
}
