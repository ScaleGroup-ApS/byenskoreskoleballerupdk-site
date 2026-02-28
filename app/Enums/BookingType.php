<?php

namespace App\Enums;

enum BookingType: string
{
    case DrivingLesson = 'driving_lesson';
    case TheoryLesson = 'theory_lesson';
    case TrackDriving = 'track_driving';
    case SlipperyDriving = 'slippery_driving';
    case Exam = 'exam';
}
