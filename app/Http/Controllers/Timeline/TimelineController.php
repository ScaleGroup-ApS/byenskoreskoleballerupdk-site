<?php

namespace App\Http\Controllers\Timeline;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TimelineController extends Controller
{
    /** @var array<string, string> Maps event class names to their category */
    private const array EVENT_CATEGORIES = [
        'App\Events\StudentEnrolled' => 'student',
        'App\Events\StudentStatusChanged' => 'student',
        'App\Events\StudentDeleted' => 'student',
        'App\Events\StudentLoginLinkSent' => 'student',
        'App\Events\BookingCreated' => 'booking',
        'App\Events\BookingUpdated' => 'booking',
        'App\Events\BookingCompleted' => 'booking',
        'App\Events\BookingCancelled' => 'booking',
        'App\Events\BookingNoShow' => 'booking',
        'App\Events\OfferAssigned' => 'payment',
        'App\Events\PaymentRecorded' => 'payment',
        'App\Events\EnrollmentRequested' => 'enrollment',
        'App\Events\EnrollmentApproved' => 'enrollment',
        'App\Events\EnrollmentRejected' => 'enrollment',
        'App\Events\StripePaymentCompleted' => 'enrollment',
    ];

    public function __invoke(): Response
    {
        $events = DB::table('verb_events')
            ->orderByDesc('created_at')
            ->paginate(50);

        $events->through(function (object $event) {
            return [
                'id' => $event->id,
                'event_type' => Str::afterLast($event->type, '\\'),
                'category' => self::EVENT_CATEGORIES[$event->type] ?? 'other',
                'data' => json_decode($event->data, true),
                'occurred_at' => $event->created_at,
            ];
        });

        return Inertia::render('timeline/index', [
            'events' => $events,
        ]);
    }
}
