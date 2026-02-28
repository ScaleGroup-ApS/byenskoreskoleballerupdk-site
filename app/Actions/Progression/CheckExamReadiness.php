<?php

namespace App\Actions\Progression;

use App\Models\Student;
use App\States\StudentProgressionState;

class CheckExamReadiness
{
    /**
     * @return array{
     *     is_ready: bool,
     *     completed: array<string, int>,
     *     required: array<string, int>,
     *     missing: array<string, int>
     * }
     */
    public function handle(Student $student): array
    {
        $student->loadMissing('offers');

        $state = StudentProgressionState::load($student->id);
        $completed = $state->lesson_counts;

        $required = [
            'driving_lesson' => 0,
            'theory_lesson' => 0,
            'track_driving' => 0,
            'slippery_driving' => 0,
        ];

        foreach ($student->offers as $offer) {
            $required['driving_lesson'] += $offer->driving_lessons;
            $required['theory_lesson'] += $offer->theory_lessons;

            if ($offer->track_required) {
                $required['track_driving'] = max(1, $required['track_driving']);
            }

            if ($offer->slippery_required) {
                $required['slippery_driving'] = max(1, $required['slippery_driving']);
            }
        }

        $missing = [];

        foreach ($required as $type => $needed) {
            $done = $completed[$type] ?? 0;

            if ($done < $needed) {
                $missing[$type] = $needed - $done;
            }
        }

        return [
            'is_ready' => empty($missing),
            'completed' => $completed,
            'required' => $required,
            'missing' => $missing,
        ];
    }
}
